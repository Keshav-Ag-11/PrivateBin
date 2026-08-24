/**
 * test_phase1.js
 * Test Envelope Encryption in combined/js/privatebin.js before any Argon2id/Decoy changes.
 */

require('jsdom-global')();
const { Crypto } = require('@peculiar/webcrypto');
const webCrypto = new Crypto();

Object.defineProperty(global, 'crypto', { value: webCrypto, configurable: true, writable: true });
Object.defineProperty(window, 'crypto', { value: webCrypto, configurable: true, writable: true });

const { Buffer } = require('buffer');
global.atob = (encoded) => Buffer.from(encoded, 'base64').toString('binary');
global.btoa = (text) => Buffer.from(text, 'binary').toString('base64');

global.zlib = require('./zlib').zlib;
require('./prettify');
global.prettyPrint = window.PR ? window.PR.prettyPrint : function() {};
global.prettyPrintOne = window.PR ? window.PR.prettyPrintOne : function() {};
global.showdown = require('./showdown-2.1.0');
global.DOMPurify = require('./purify-3.4.12');
global.baseX = require('./base-x-5.0.1').baseX;
global.Legacy = require('./legacy').Legacy;

require('./privatebin');
const PrivateBin = window.PrivateBin;
PrivateBin.Controller.initZlib();

function arraybufferToString(buffer) {
    return String.fromCharCode.apply(null, new Uint8Array(buffer));
}

async function testEnvelope() {
    console.log("=== PHASE 1: TESTING ENVELOPE ENCRYPTION IN combined/ ===");

    // 1. Generate recipient keypairs
    const aliceKp = await PrivateBin.CryptTool.generateRecipientKeypair();
    const bobKp = await PrivateBin.CryptTool.generateRecipientKeypair();
    const maryKp = await PrivateBin.CryptTool.generateRecipientKeypair();

    const alicePrivB64 = btoa(arraybufferToString(aliceKp.privateKeyBytes));
    const bobPrivB64 = btoa(arraybufferToString(bobKp.privateKeyBytes));
    const maryPrivB64 = btoa(arraybufferToString(maryKp.privateKeyBytes));

    const alicePubStr = arraybufferToString(aliceKp.publicKeyBytes);
    const bobPubStr = arraybufferToString(bobKp.publicKeyBytes);
    const maryPubStr = arraybufferToString(maryKp.publicKeyBytes);

    console.log("  [1] Generated keypairs for Alice, Bob, Mary.");

    // 2. Mock 32-byte CEK and wrap for recipients
    const cek = new Uint8Array(32);
    for (let i = 0; i < 32; i++) cek[i] = i * 7 + 3;
    const cekStr = arraybufferToString(cek);

    const aliceWrap = await PrivateBin.CryptTool.wrapKeyForRecipient(cekStr, alicePubStr);
    const bobWrap = await PrivateBin.CryptTool.wrapKeyForRecipient(cekStr, bobPubStr);
    const maryWrap = await PrivateBin.CryptTool.wrapKeyForRecipient(cekStr, maryPubStr);

    console.log("  [2] Wrapped CEK for Alice, Bob, Mary.");

    // 3. Unwrap for each recipient
    const aliceUnwrappedStr = await PrivateBin.CryptTool.unwrapKeyForRecipient(
        aliceWrap.wrappedKey, aliceWrap.ephemeralPublicKey, alicePrivB64
    );
    const bobUnwrappedStr = await PrivateBin.CryptTool.unwrapKeyForRecipient(
        bobWrap.wrappedKey, bobWrap.ephemeralPublicKey, bobPrivB64
    );
    const maryUnwrappedStr = await PrivateBin.CryptTool.unwrapKeyForRecipient(
        maryWrap.wrappedKey, maryWrap.ephemeralPublicKey, maryPrivB64
    );

    const aliceMatch = aliceUnwrappedStr === cekStr;
    const bobMatch = bobUnwrappedStr === cekStr;
    const maryMatch = maryUnwrappedStr === cekStr;

    console.log("  -> Alice unwrap match:", aliceMatch ? "PASS" : "FAIL");
    console.log("  -> Bob unwrap match:", bobMatch ? "PASS" : "FAIL");
    console.log("  -> Mary unwrap match:", maryMatch ? "PASS" : "FAIL");

    // 4. Cross-recipient failure test
    let crossFailed = false;
    try {
        await PrivateBin.CryptTool.unwrapKeyForRecipient(
            bobWrap.wrappedKey, bobWrap.ephemeralPublicKey, alicePrivB64
        );
    } catch(e) {
        crossFailed = true;
    }
    console.log("  -> Alice key cannot unwrap Bob envelope:", crossFailed ? "PASS" : "FAIL");

    const allPass = aliceMatch && bobMatch && maryMatch && crossFailed;
    console.log("\nPHASE 1 ENVELOPE TEST RESULT:", allPass ? "PASS" : "FAIL");
    process.exit(allPass ? 0 : 1);
}

testEnvelope().catch(e => {
    console.error("Test failed with error:", e);
    process.exit(1);
});
