// scripts/generateFilesToCache.cjs
const fs = require("fs");
const path = require("path");

try {
	const assetsDir = path.join(__dirname, "../build/assets");

	console.log("Looking for assets in:", assetsDir);

	if (!fs.existsSync(assetsDir)) {
		console.error("Assets directory does not exist:", assetsDir);
		process.exit(1);
	}

	const assetFiles = fs
		.readdirSync(assetsDir)
		.map((f) => `/build/assets/${f}`);

	const filesToCache = [
		"/offline.html",
		"/build/manifest.json",
		...assetFiles,
	];

	const outputPath = path.join(__dirname, "../cache.json");
	console.log("Writing cache.json to:", outputPath);

	fs.writeFileSync(outputPath, JSON.stringify(filesToCache, null, 2));

	console.log(
		"Successfully generated cache.json with",
		filesToCache.length,
		"files",
	);
} catch (error) {
	console.error("Error generating cache.json:", error);
	process.exit(1);
}