import requests
import json
from requests_toolbelt import MultipartEncoder
from hashlib           import sha512

class LLUploader():

    def __init__(self, url, passwordHash, fileHandle, fileName, callback = None):

        self.response = None

        self.url          = url
        self.passwordHash = passwordHash
        self.fileHandle   = fileHandle
        self.fileName     = fileName

        fileHash = sha512()
        for chunk in iter(lambda: fileHandle.read(8196), b''):
                fileHash.update(chunk)
        fileHandle.seek(0) # needed before the request module's read
        self.fileHash = fileHash.hexdigest()

        self.headers = {
            'passwordHash': self.passwordHash,
            'fileHash':     self.fileHash,
            'fileName':     self.fileName
        }

        self.data = MultipartEncoder(fields = {
                'headers': ('headers', json.dumps(self.headers),  ''),
                'data':    ('data',    fileHandle,                '')
            },
            callback = callback
        )

    def upload(self):

        self.response = json.loads(requests.post(
            self.url + '?j',
            data = self.data,
            headers = { 'Content-Type': self.data.content_type  }
        ).text)

        return self
