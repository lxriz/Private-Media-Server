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

cursor.execute("SELECT Vector FROM Metadata JOIN Vectors ON Metadata.ID = Vectors.ID WHERE Hash = ?", (hash,))

vector_search = cursor.fetchone()


if vector_search is None:
    exit(-1);

vector_search = pickle.loads(vector_search[0])

cursor.execute("SELECT Hash, Vector FROM Metadata JOIN Vectors ON Vectors.ID = Metadata.ID")
query = cursor.fetchall()
cursor.close()
connection.close()


top_matches = []
max_matches = 3
# IDK what is better so we just use both ig
if False:
    for row in query:
        if row[0] == hash:
            continue
        # Sinusgleicheit
        dist1 = np.dot(vector_search, pickle.loads(row[1]))
        dist2 = np.linalg.norm(pickle.loads(row[1])) * np.linalg.norm(vector_search)
        dist = dist1/dist2
    
        top_matches.append([dist, row[0]])
        top_matches.sort(key=lambda x: x[0], reverse=True)
        if len(top_matches) > max_matches:
            top_matches.pop(len(top_matches)-1)

else:
    for row in query:
        if row[0] == hash:
            continue
        # Euklitischer Abstand
        dist = np.linalg.norm(((vector_search-pickle.loads(row[1]))))

        top_matches.append([dist, row[0]])
        top_matches.sort(key=lambda x: x[0])
        if len(top_matches) > max_matches:
            top_matches.pop(len(top_matches)-1)



out = ""
for pic in top_matches:
    out += pic[1] + " "

print(out)
exit(0)








