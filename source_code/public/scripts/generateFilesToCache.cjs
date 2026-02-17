// scripts/generateFilesToCache.cjs
const fs = require('fs');
const path = require('path');

const assetsDir = path.join(__dirname, '../build/assets');
const assetFiles = fs.readdirSync(assetsDir).map(f => `/build/assets/${f}`);

const filesToCache = [
	'/offline.html',
	'/build/manifest.json',
	...assetFiles
];

fs.writeFileSync(
	path.join(__dirname, '../cache.json'),
	JSON.stringify(filesToCache, null, 2)
);
