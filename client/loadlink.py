#!/usr/bin/env python

import sys
from os.path      import basename, expanduser
from configparser import ConfigParser
from PyQt4        import QtGui
from lluploader   import LLUploader

app       = QtGui.QApplication(sys.argv)
trayIcon  = QtGui.QSystemTrayIcon(QtGui.QIcon("iconwhite.svg"), app)
clipboard = QtGui.QApplication.clipboard()

configFilePath = expanduser('~/.lluploader')
with open(configFilePath, 'r') as configFile:
    config = ConfigParser()
    config.read_file(configFile)
    url = config['LLUploader']['url']
    passwordHash = config['LLUploader']['passwordHash']

def upload():
    if clipboard.text()[0:7] == 'file://':
        filePath   = clipboard.text()[7:]
        fileHandle = open(filePath, 'rb')
        fileName   = basename(filePath)
        u = LLUploader(url, passwordHash, fileHandle, fileName)
        u.upload()
        rURL = u.response['url']
        print(rURL, file = sys.stderr)
        clipboard.setText(rURL)
        trayIcon.showMessage(rURL,
            fileName + ' uploaded and URL copied to the clipboard.',
            msecs = 2000)

trayMenu    = QtGui.QMenu()
pasteAction = trayMenu.addAction("Paste")
quitAction  = trayMenu.addAction("Quit")
pasteAction.triggered.connect(upload)
quitAction.triggered.connect(QtGui.qApp.quit)
trayIcon.setContextMenu(trayMenu)
trayIcon.show()

sys.exit(app.exec_())
