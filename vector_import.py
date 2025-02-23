import torch
from torchvision import models, transforms
from PIL import Image
from annoy import AnnoyIndex
import pickle
import sqlite3
import argparse

# Create the parser
parser = argparse.ArgumentParser()

# Add arguments
parser.add_argument('hash', type=str, help="Hash of media to look for")
parser.add_argument('path', type=str, help="Path where media is to find")

# Parse the arguments
args = parser.parse_args()
hash = args.hash
path = args.path

# Load a pretrained ResNet18 model
model = models.resnet18(weights=True)
model = torch.nn.Sequential(*(list(model.children())[:-1]))  # Remove the final classification layer
model.eval()  # Set the model to evaluation mode

# Define a transform to preprocess the input image
preprocess = transforms.Compose([
    transforms.Resize(256),
    transforms.CenterCrop(224),
    transforms.ToTensor(),
    transforms.Normalize(mean=[0.485, 0.456, 0.406], std=[0.229, 0.224, 0.225]),
])

def extract_feature_vector(image_path):
    img = Image.open(image_path).convert('RGB')
    img_t = preprocess(img)
    batch_t = torch.unsqueeze(img_t, 0)  # Add batch dimension

    with torch.no_grad():
        features = model(batch_t)
    return pickle.dumps(features.squeeze().numpy())  # Flatten to 1D array

# Create or connect to the SQLite database
connection = sqlite3.connect('database.db', timeout=5.0)
cursor = connection.cursor()

cursor.execute("SELECT ID FROM Metadata WHERE Hash=?", (hash,))
id=cursor.fetchone()[0]
if id is not None:
    cursor.execute("INSERT INTO Vectors (ID, Vector) VALUES (?,?)", (id, extract_feature_vector(path)))
        
connection.commit()
connection.close()
