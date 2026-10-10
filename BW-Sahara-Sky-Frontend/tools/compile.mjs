import {spawnSync} from 'node:child_process';
import {fileURLToPath} from 'node:url';
import {backendMode,configureDeployment} from './deployment-config.mjs';
const mode=backendMode();
for(const file of ['tools/build.mjs','tools/prerender.mjs']){const result=spawnSync(process.execPath,[file],{stdio:'inherit',env:process.env});if(result.status!==0)process.exit(result.status||1);}
await configureDeployment(fileURLToPath(new URL('../dist/',import.meta.url)),mode);
