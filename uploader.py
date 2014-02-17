#!/usr/bin/env python

import requests
import json
from sys import argv
from os.path import basename
from hashlib import md5, sha1

url          = argv[1]
passwordHash = sha1(argv[2].encode('utf-8')).hexdigest()
filePath     = argv[3]

s = md5()
with open(filePath, 'rb') as f:
    for chunk in iter(lambda: f.read(32768), b''):
        s.update(chunk)
fileHash = s.hexdigest()

headers = { 'passwordHash': passwordHash, 'fileHash': fileHash, 'fileName': basename(filePath) }

r = requests.post(url, files = { 'headers': json.dumps(headers), 'data': open(filePath, 'rb') })

print(r.text)
