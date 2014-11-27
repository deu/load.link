#!/bin/bash

TWIG_VERSION="1.16.2"
SCSS_VERSION="0.1.1"
JSQUEEZE_VERSION="1.0.5"
PRISMJS_VERSION="4160c577691b74223f3b5515bb920236b96a87e1"
VIDEOJS_VERSION="4.10.2"
FAENZA_ICONS_URL="https://faenza-icon-theme.googlecode.com/files/faenza-icon-theme_1.3.zip"

mkdir tmp
cd tmp

## Libraries

# Twig
git clone git://github.com/twigphp/Twig.git
cd Twig
git checkout tags/v${TWIG_VERSION}
mv lib/Twig ../../lib/
cd ..

# SCSS
git clone git://github.com/leafo/scssphp.git
cd scssphp
git checkout tags/v${SCSS_VERSION}
rm .gitignore
rm package.sh
rm todo
rm -r site
rm -rf .git
cd ..
mv scssphp ../lib/SCSS

# Twig
git clone https://github.com/nicolas-grekas/JSqueeze.git
cd JSqueeze
git checkout tags/v${JSQUEEZE_VERSION}
rm -rf .git
cd ..
mv JSqueeze ../lib/

## Default theme related

THEME_STATIC_PATH=../themes/DarkAndDark/static

# prism.js (Syntax Highlighter)
mkdir ${THEME_STATIC_PATH}/prismjs
git clone https://github.com/LeaVerou/prism.git
cd prism
git checkout ${PRISMJS_VERSION}
cat themes/prism-okaidia.css plugins/{line-numbers/prism-line-numbers.css,line-highlight/prism-line-highlight.css} > ../${THEME_STATIC_PATH}/prismjs/prism.css
cat components/prism-{core,clike,markup,javascript,bash,c,coffeescript,cpp,csharp,css,css-extras,go,haskell,ini,java,latex,objectivec,php,php-extras,python,ruby,scss,sql,swift,twig}.min.js plugins/{line-numbers/prism-line-numbers.min.js,line-highlight/prism-line-highlight.min.js} > ../${THEME_STATIC_PATH}/prismjs/prism.js
cd ..

# video.js
mkdir ${THEME_STATIC_PATH}/videojs
git clone https://github.com/videojs/video.js.git
cd video.js
git checkout tags/v${VIDEOJS_VERSION}
cp dist/video-js/video.js ../${THEME_STATIC_PATH}/videojs/video.js
cp dist/video-js/video-js.min.css ../${THEME_STATIC_PATH}/videojs/video.css
cp dist/video-js/video-js.swf ../${THEME_STATIC_PATH}/videojs/video.swf
cp -r dist/video-js/font ../${THEME_STATIC_PATH}/videojs/
cd ..

# Faenza icons
mkdir ${THEME_STATIC_PATH}/faenzaicons
wget ${FAENZA_ICONS_URL} -O faenza_icons.zip
mkdir faenza_icons
cd faenza_icons
unzip ../faenza_icons.zip
tar xzf Faenza.tar.gz
cd Faenza/mimetypes/96
mkdir ../icons_tmp
cp -L * ../icons_tmp/
cd ../icons_tmp
rm *.icon
rename "gnome-mime-" "" *
cd ..
mv icons_tmp ../../../
cd ../../..
cp -r icons_tmp/*-* ${THEME_STATIC_PATH}/faenzaicons/
cp icons_tmp/none.png ${THEME_STATIC_PATH}/faenzaicons/
