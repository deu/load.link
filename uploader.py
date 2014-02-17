#!/usr/bin/env python

import requests
import json
from sys import argv
from os.path import basename
from hashlib import sha512

url          = argv[1]
passwordHash = sha512(argv[2].encode('utf-8')).hexdigest()
filePath     = argv[3]

s = sha512()
with open(filePath, 'rb') as f:
    for chunk in iter(lambda: f.read(8196), b''):
        s.update(chunk)
fileHash = s.hexdigest()

headers = { 'passwordHash': passwordHash, 'fileHash': fileHash, 'fileName': basename(filePath) }

response = requests.post(url, files = { 'headers': json.dumps(headers), 'data': open(filePath, 'rb') })

r = json.loads(response.text)

print(r.get('url', r.get('error', 'Client side unknown error.')))
