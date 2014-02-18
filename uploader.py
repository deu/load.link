#!/usr/bin/env python

import requests
import json
import progressbar
from sys import argv
from requests_toolbelt import MultipartEncoder
from os.path import basename
from hashlib import sha512

class LLUploader():

    def __init__(self, url, passwordHash, filePath, callback = None):

        self.response = None

        self.url          = url
        self.passwordHash = passwordHash
        self.filePath     = filePath

        s = sha512()
        with open(self.filePath, 'rb') as f:
            for chunk in iter(lambda: f.read(8196), b''):
                s.update(chunk)
        self.fileHash = s.hexdigest()

        self.headers = {
            'passwordHash': self.passwordHash,
            'fileHash':     self.fileHash,
            'fileName':     basename(self.filePath)
        }

        self.files = MultipartEncoder(fields = {
            'headers': ('headers', json.dumps(self.headers),  ''),
            'data':    ('data',    open(self.filePath, 'rb'), '')
            },
            callback = callback
        )

    def upload(self):

        self.response = requests.post(
            url,
            data = self.files,
            headers = { 'Content-Type': self.files.content_type  }
        )

        return self


if __name__ == '__main__':

    url          = argv[1]
    passwordHash = sha512(argv[2].encode('utf-8')).hexdigest()
    filePath     = argv[3]

    pbar = progressbar.ProgressBar()

    u = LLUploader(url, passwordHash, filePath,
        callback = lambda files: pbar.update(files.bytes_read))

    pbar.maxval = len(u.files)
    pbar.start()
    u.upload()
    pbar.finish()

    r = json.loads(u.response.text)

    print(r.get('url', r.get('error', 'Client side unknown error.')))
