// Loopback-only SMTP sink for local fixture runs: accepts mail from the local Laravel fixture
// (MAIL_MAILER=smtp, MAIL_HOST=127.0.0.1, MAIL_PORT=<port>) and keeps it in memory. Nothing is relayed.
import {createServer} from 'node:net';

export function startSmtpSink(port = 2525) {
 const messages = [];
 const server = createServer(socket => {
  if (!['127.0.0.1', '::1', '::ffff:127.0.0.1'].includes(socket.remoteAddress)) {socket.destroy();return;}
  let buffer = '', data = null, to = [];
  const say = line => socket.write(line + '\r\n');
  say('220 fixture-sink ESMTP');
  socket.on('data', chunk => {
   buffer += chunk.toString('utf8');
   let index;
   while ((index = buffer.indexOf('\r\n')) >= 0) {
    const line = buffer.slice(0, index);buffer = buffer.slice(index + 2);
    if (data !== null) {
     if (line === '.') {messages.push({to, raw: data.join('\n'), at: Date.now()});data = null;to = [];say('250 queued in memory');}
     else data.push(line.startsWith('..') ? line.slice(1) : line);
     continue;
    }
    const command = line.slice(0, 4).toUpperCase();
    if (command === 'EHLO') {socket.write('250-fixture-sink\r\n250 8BITMIME\r\n');}
    else if (command === 'HELO' || command === 'MAIL' || command === 'RSET' || command === 'NOOP') say('250 OK');
    else if (command === 'RCPT') {to.push((line.match(/<([^>]+)>/) || [])[1]);say('250 OK');}
    else if (command === 'DATA') {data = [];say('354 end with .');}
    else if (command === 'QUIT') {say('221 bye');socket.end();}
    else say('502 not implemented');
   }
  });
  socket.on('error', () => {});
 });
 return new Promise((resolve, reject) => {
  server.once('error', reject);
  server.listen(port, '127.0.0.1', () => resolve({
   messages,
   // Quoted-printable may wrap the code; the OTP mail repeats it, so the first 6-digit run is enough.
   async codeFor(email, since, timeout = 15000) {
    const end = Date.now() + timeout;
    while (Date.now() < end) {
     const message = messages.filter(m => m.at >= since && m.to.includes(email)).at(-1);
     const code = message?.raw.replace(/=\r?\n/g, '').match(/verification code is (\d{6})/)?.[1];
     if (code) return code;
     await new Promise(r => setTimeout(r, 150));
    }
    throw new Error('No OTP mail for ' + email);
   },
   close: () => new Promise(r => server.close(r)),
  }));
 });
}
