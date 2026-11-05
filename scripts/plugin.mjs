// Bygger dist/ og pakker WordPress-pluginet om-teknik-app.zip.
import fs from 'node:fs';
import path from 'node:path';
import archiver from 'archiver';
import { build, ROOT, copyDir } from '../lib/build.mjs';

const SLUG = 'om-teknik-app';

const { cfg, warnings, outDir, version } = await build();
const stage = path.join(ROOT, '.cache', 'plugin', SLUG);
fs.rmSync(path.dirname(stage), { recursive: true, force: true });
copyDir(path.join(ROOT, 'plugin', SLUG), stage);
copyDir(outDir, path.join(stage, 'app'));
fs.rmSync(path.join(stage, 'app', '.htaccess'), { force: true }); // pluginet sætter selv headers

const vars = {
  APP_NAME: cfg.appName,
  APP_NAME_PHP: cfg.appName.replace(/\\/g, '\\\\').replace(/'/g, "\\'"),
  VERSION: cfg.version,
  BASE_SLUG: cfg.basePath.replace(/^\/|\/$/g, ''),
  INSTALL_SLUG: cfg.pages.install.slug,
  PUBLISHER: (cfg.footer.publisher || cfg.appName).replace(/^Udgivet af /i, '').replace(/[\r\n*]/g, ' '),
  CONTRIBUTORS: cfg.wpContributors || '',
  DESCRIPTION: cfg.description,
  BUILT_AT: new Date().toISOString().slice(0, 10) + ` (${version})`,
};
for (const f of ['om-teknik-app.php', 'readme.txt']) {
  const p = path.join(stage, f);
  let s = fs.readFileSync(p, 'utf8');
  s = s.replace(/\{\{([A-Z_]+)\}\}/g, (m, k) => (k in vars ? vars[k] : m));
  fs.writeFileSync(p, s);
}

const zipFile = path.join(ROOT, `${SLUG}.zip`);
fs.rmSync(zipFile, { force: true });
await new Promise((resolve, reject) => {
  const out = fs.createWriteStream(zipFile);
  const zip = archiver('zip', { zlib: { level: 9 } });
  out.on('close', resolve);
  zip.on('error', reject);
  zip.pipe(out);
  zip.directory(stage, SLUG);
  zip.finalize();
});
const kb = Math.round(fs.statSync(zipFile).size / 1024);
console.log(`\nPakkede ${path.basename(zipFile)} (${kb} kB). Upload den under Plugins → Tilføj nyt → Upload plugin.`);
if (warnings.length) console.log(`Bemærk: buildet gav ${warnings.length} advarsel(er) – se ovenfor.`);
