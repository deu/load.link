#!/usr/bin/env python

import requests
import json
import progressbar as pbar
from sys               import stderr, argv, exit
from time              import sleep
from os.path           import basename, expanduser
from argparse          import ArgumentParser, HelpFormatter
from configparser      import ConfigParser
from tempfile          import NamedTemporaryFile
from zipfile           import ZipFile
from getpass           import getpass
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

        self.response = requests.post(
            self.url + '?j',
            data = self.data,
            headers = { 'Content-Type': self.data.content_type  }
        )

        return self

def createConfigFile(configFilePath):

        i = None
        while (i not in ( 'yes', 'no', '' )):
            i = input('Do you want to create a configuration file? [yes] ')

        if (i == 'no'):
            exit()
            return

        accessString = None
        while (not accessString):
            accessString = input('Paste your Access Sting: ')

        try:
            url, passwordHash = accessString.split('|')
        except ValueError:
            print('The access string you entered is invalid.')

            i = None
            while (i not in ( 'yes', 'no', '' )):
                i = input('Do you want manually insert URL and Password? [no] ')

            if (i == 'no'):
                print('No configuration file has been created.')
                exit()
                return

            url = None
            while (not url):
                url = input('Insert the URL to your load.link installation: ')

            if not '://' in url:
                url = 'http://' + url

            password = None
            while (not password):
                password = getpass('Insert your password (it won\'t show): ')

            passwordHash = sha512(password.encode('utf-8')).hexdigest()

        config = ConfigParser()

        config['LLUploader'] = {
            'url':          url,
            'passwordHash': passwordHash
        }

        with open(configFilePath, 'w') as configFile:
            config.write(configFile)

        print('Configuration successfully saved.')

if __name__ == '__main__':

    configFilePath = expanduser('~/.lluploader')

    parser = ArgumentParser(
        prog = 'lluploader',
        description = 'Upload files to a load.link server.',
        formatter_class = lambda prog:
            HelpFormatter(prog, max_help_position = 80)
    )

    parser.add_argument('-u', '--url',
        help    = 'the url to the load.link server')
    parser.add_argument('-p', '--password',
        help    = 'the password')
    parser.add_argument('FILE', nargs='+',
        help    = 'the file(s) you want to upload')
    parser.add_argument('-z', '--zipfile',
        help    = 'compress the file(s) into ZIPFILE.zip before uploading')
    parser.add_argument('-P', '--hideprogress',
        action  = 'store_const',
        const   = True,
        help    = 'hide the progress bar')
    parser.add_argument('-c', '--config',
        action  = 'store_const',
        const   = True,
        help    = 'create a new configuration file')

    # Gotta bypass argparse here:
    if list(set(argv) & { '-c', '--config' }):
        createConfigFile(configFilePath)
        exit()

    args = parser.parse_args()
    showProgress = not args.hideprogress

    files = []

    for filePath in args.FILE:
        try:
            files.append({
                'handle': open(filePath, 'rb'),
                'name':   basename(filePath)
            })
        except IOError:
            print(filePath + ': not found', file = stderr)
            exit()

    if args.zipfile:

        tmpFile = NamedTemporaryFile()

        with ZipFile(tmpFile.name, 'w') as zip:
            for file in files:
                zip.write(file['handle'].name)
            zip.close()

        files = [ { # do NOT append
            'handle': tmpFile,
            'name':   (args.zipfile if args.zipfile[-4:] == '.zip'
                  else args.zipfile + '.zip')
        } ]

    if (args.url and args.password):
        url          = args.url if '://' in args.url else 'http://' + args.url
        passwordHash = sha512(args.password.encode('utf-8')).hexdigest()

    else:

        while True:
            try:
                with open(configFilePath, 'r') as configFile:
                    config = ConfigParser()
                    config.read_file(configFile)
                    url          = config['LLUploader']['url']
                    passwordHash = config['LLUploader']['passwordHash']
                    break
            except IOError:
                print('I couldn\'t find a valid configuration file.')
                createConfigFile(configFilePath)

    if showProgress:
        b = pbar.ProgressBar(
            widgets = [
                pbar.Percentage(), ' ',
                pbar.Bar(), ' ',
                pbar.FileTransferSpeed(), '    ',
                pbar.ETA()
            ]
        )

        b.maxval = 0

    uploads = []
    for file in files:

        u = LLUploader(url, passwordHash, file['handle'], file['name'],
            callback = (lambda data: b.update(data.bytes_read))
                        if showProgress else None)
        if showProgress:
            b.maxval += len(u.data)

        uploads.append(u)

    if showProgress:
        b.start()

    for upload in uploads:
        try:
            upload.upload()
        except:
            if showProgress:
                b.start()
                print('') # the next print overwrites the pbar otherwise
            print('Impossible to connect to ' + url, file = stderr)
            exit()

    if showProgress:
        b.finish()

    for upload in uploads:
        r = json.loads(upload.response.text)
        rURL   = r.get('url',   False)
        rError = r.get('error', False)
        if rURL:
            print(rURL)
        else:
            print(upload.filePath + ': ', file = stderr)
            if rError:
                print('server returned an error: ' + rError, file = stderr)
            else:
                print('server returned an invalid response', file = stderr)
