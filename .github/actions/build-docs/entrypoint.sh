#!/bin/sh -l

# build docs
cd docs
npm install
npm run build

# move docs into reference
mv public/* ../build
