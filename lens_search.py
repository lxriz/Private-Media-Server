import numpy as np
import sqlite3
import pickle
import argparse
import random as r

# Create the parser
parser = argparse.ArgumentParser()

# Add arguments
parser.add_argument('hash', type=str, help="Hash of media to look for")

# Parse the arguments
args = parser.parse_args()
hash = args.hash

connection = sqlite3.Connection("database.db")
cursor = connection.cursor()

# Check if hash is in database
cursor.execute("SELECT Vector FROM Metadata JOIN Vectors ON Metadata.ID = Vectors.ID WHERE Hash = ?", (hash,))

vector = cursor.fetchone()

if vector is None:
    exit(-1);


# Get Parameters
vector = pickle.loads(vector[0])


cursor.execute("SELECT ID FROM Metadata WHERE Hash = ?", (hash,))
id = cursor.fetchone()[0]
cursor.execute("SELECT COUNT(*) FROM Catalog WHERE ID_Metadata = ?", (id,))
count_tags = cursor.fetchone()[0]


cursor.execute("SELECT Hash, Vector, Metadata.ID FROM Metadata JOIN Vectors ON Vectors.ID = Metadata.ID")
query = cursor.fetchall()


# Search parameters
top_matches = []
max_matches = 3

for row in query:
    if row[0] == hash:
        continue

    
    tags_score = 0
    if count_tags > 0:
        cursor.execute("SELECT COUNT(*) FROM (SELECT ID_Tag FROM Catalog WHERE ID_Metadata = ? INTERSECT SELECT ID_Tag FROM Catalog WHERE ID_Metadata = ?)", (id, row[2] ))
        tags_score = cursor.fetchone()[0] / count_tags
    
    
    # Euklitischer Abstand
    score = (np.linalg.norm(((vector-pickle.loads(row[1])))))**(1-0.2*tags_score)

    # Get top 3
    top_matches.append([score, row[0]])
    top_matches.sort(key=lambda x: x[0])
    if len(top_matches) > max_matches:
        top_matches.pop(len(top_matches)-1)


cursor.close()
connection.close()

out = ""
for pic in top_matches:
    out += pic[1] + " "

print(out)
exit(0)








