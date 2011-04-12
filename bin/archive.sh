#!/bin/bash
#
# Make Extension Archive

if [ "$1" == "" ]; then
	echo Usage: $0 "[extension-name|all]"
	exit
fi

EXTENSION=`basename $1`

if [ "$EXTENSION" == "all" ]; then
	echo Does not work yet.
	exit
fi

if [ ! -d "$EXTENSION" ]; then
	echo 'Cannot find extension "'$EXTENSION'".'
	exit
fi

echo 'Archiving extension "'$EXTENSION'".'

pushd `dirname $0` >& /dev/null
cd ..

FNAME=archives/$EXTENSION-latest.zip
EXTDIR=$EXTENSION/
VERSION=`head -3 $EXTDIR/{admin,hook,snippet,widget}_*.php 2> /dev/null \
	| tr -d ' ' | grep 'Version:' | uniq | sed -e 's/[^:]*://'`
VERSION_FNAME=""

if [ "$VERSION" != "" ]; then
	if [ `echo "$VERSION" | wc -l` -gt 1 ]; then
		echo "Multiple version numbers found."
	else
		VERSION_FNAME=archives/$EXTENSION-$VERSION.zip
	fi
else
	echo "No version number found."
fi

if [ -f files.lst ]; then
	rm files.lst
fi
find $EXTDIR | grep -v svn | grep -v [.]bup > files.lst

if [ -f $FNAME ]; then
	rm $FNAME
fi
zip -9rq $FNAME . -i@files.lst
echo Updated $FNAME.
rm files.lst

if [ "$VERSION_FNAME" != "" ]; then
	if [ -f $VERSION_FNAME ]; then
		rm $VERSION_FNAME
	fi
	cp $FNAME $VERSION_FNAME
	echo Updated $VERSION_FNAME.
fi

popd >& /dev/null
