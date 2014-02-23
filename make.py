#!/usr/bin/env python

import os
import stat
import platform
from sys      import exit
from argparse import ArgumentParser, HelpFormatter
from shutil   import copyfile

class Make:

    platforms = ( 'Linux', 'Windows', 'MacOS' )
    targets   = ( 'all', 'server', 'client', 'lluploader' )

    def __init__(self, target, platform, distdir):

        self.target   = target
        self.platform = platform
        self.distdir  = distdir if distdir[-1:] == '/' else distdir + '/'

    def dist(self):

        getattr(self, '_' + self.target)()

    def _all(self):

        self._server()
        self._client()
        self._lluploader()

    def _server(self):

        d = self.distdir + 'Server/'
        if not os.path.exists(d):
            os.makedirs(d)
        copyfile('Server/index.php', d + '/index.php')

    def _client(self):

        getattr(self, {
            'Linux':   '_client_linux',
            'Windows': '_client_windows',
            'MacOS':   '_client_macos'
        }
        [self.platform])()

    def _client_linux(self):
        ...

    def _client_windows(self):
        ...

    def _client_macos(self):
        ...

    def _lluploader(self):

        lluploader = self.distdir + 'lluploader'
        copyfile('Client/lluploader.py', lluploader)
        self.makeExecutable(lluploader)

    def makeExecutable(self, filePath):

        with open(filePath, 'r') as fileHandler:
            mode  = os.fstat(fileHandler.fileno()).st_mode
            mode |= stat.S_IXUSR | stat.S_IXGRP | stat.S_IXOTH
            os.fchmod(fileHandler.fileno(), stat.S_IMODE(mode))


if __name__ == '__main__':

    parser = ArgumentParser(
        prog = 'make.py',
        formatter_class = lambda prog:
            HelpFormatter(prog, max_help_position = 80)
    )

    parser.add_argument('--platform', nargs='?',
        choices = Make.platforms)
    parser.add_argument('--target', nargs='?',
        choices = Make.targets)
    parser.add_argument('--distdir', nargs='?')

    args = parser.parse_args()

    if not args.platform:
        args.platform = platform.system()
    if args.platform not in Make.platforms:
        print('Invalid platform: ' + args.platform)
        exit()

    if not args.target:
        args.target = 'all'
    if args.target not in Make.targets:
        print('Invalid target: ' + args.target)
        exit()

    if not args.distdir:
        args.distdir = 'dist'
    try:
        if not os.path.exists(args.distdir):
            os.makedirs(args.distdir)
        os.access(args.distdir, os.W_OK)
    except:
        print('Invalid distdir: ' + args.distdir)
        exit()

    print(
        'Platform: ' + args.platform + '\n' +
        'Target:   ' + args.target   + '\n'
        'DistDir:  ' + args.distdir
    )

    make = Make(**vars(args))
    make.dist()

    print('...Done.')
