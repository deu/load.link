#!/usr/bin/env python

import requests
import json
import progressbar as pbar
from sys import argv, exit
from os.path import basename, expanduser
from argparse import ArgumentParser, HelpFormatter
from configparser import ConfigParser
from getpass import getpass
from requests_toolbelt import MultipartEncoder
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
            self.url + '?j',
            data = self.files,
            headers = { 'Content-Type': self.files.content_type  }
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
        except:
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
    files        = args.FILE
    showProgress = not args.hideprogress

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
    for filePath in files:
        u = LLUploader(url, passwordHash, filePath,
            callback = (lambda files: b.update(files.bytes_read))
                        if showProgress else None)

        uploads.append(u)

        if showProgress:
            b.maxval += len(u.files)

    if showProgress:
        b.start()

    for upload in uploads:
        upload.upload()

    if showProgress:
        b.finish()

    for upload in uploads:
        try:
            r = json.loads(upload.response.text)
            print(r.get('url',
                upload.filePath + ': server returned an error: ' + r.get('error', ''))
            )
        except ValueError:
            print(upload.filePath + ': server returned an invalid response')
