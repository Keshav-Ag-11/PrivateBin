/**
 * test_phase2.js
 * Test Phase 2: Argon2id Integration in combined/js
 */

const fs = require('fs');
const path = require('path');

// 1. jsdom & webcrypto setup
require('jsdom-global')();
const { Crypto } = require('@peculiar/webcrypto');
const webCrypto = new Crypto();
Object.defineProperty(global, 'crypto', { value: webCrypto, configurable: true, writable: true });
Object.defineProperty(window, 'crypto', { value: webCrypto, configurable: true, writable: true });

const { Buffer } = require('buffer');
global.atob = (encoded) => Buffer.from(encoded, 'base64').toString('binary');
global.btoa = (text) => Buffer.from(text, 'binary').toString('base64');

// 2. Load hashwasm UMD
const hwCode = fs.readFileSync(path.resolve(__dirname, 'argon2.umd.min.js'), 'utf8');
const hwMod = { exports: {} };
(new Function('module', 'exports', hwCode))(hwMod, hwMod.exports);
const hashwasm = hwMod.exports;
global.hashwasm = hashwasm;

// 3. Load required global dependencies
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

const base58 = new baseX('123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz');

function arraybufferToString(buffer) {
    return String.fromCharCode.apply(null, new Uint8Array(buffer));
}

// 4. Interceptors to verify Argon2id is called and PBKDF2 is not called for new pastes
let argon2idCalls = [];
let pbkdf2Calls = [];

const origArgon2id = hashwasm.argon2id.bind(hashwasm);
hashwasm.argon2id = async function(params) {
    argon2idCalls.push(params);
    return origArgon2id(params);
};

const origDeriveKey = window.crypto.subtle.deriveKey.bind(window.crypto.subtle);
window.crypto.subtle.deriveKey = async function(algorithm, ...rest) {
    if (algorithm && algorithm.name === 'PBKDF2') {
        pbkdf2Calls.push(algorithm);
    }
    return origDeriveKey(algorithm, ...rest);
};

async function runPhase2Tests() {
    console.log("=== PHASE 2: TESTING ARGON2ID INTEGRATION IN combined/ ===");

    // Test 1: Individual URL / envelope encryption still works
    console.log("\n[1] Testing Envelope Encryption keypair & wrapping...");
    const aliceKp = await PrivateBin.CryptTool.generateRecipientKeypair();
    const alicePrivB64 = btoa(arraybufferToString(aliceKp.privateKeyBytes));
    const alicePubStr = arraybufferToString(aliceKp.publicKeyBytes);

    const cek = new Uint8Array(32);
    for (let i = 0; i < 32; i++) cek[i] = i * 3 + 1;
    const cekStr = arraybufferToString(cek);

    const aliceWrap = await PrivateBin.CryptTool.wrapKeyForRecipient(cekStr, alicePubStr);
    const aliceUnwrappedStr = await PrivateBin.CryptTool.unwrapKeyForRecipient(
        aliceWrap.wrappedKey, aliceWrap.ephemeralPublicKey, alicePrivB64
    );

    const t1_pass = aliceUnwrappedStr === cekStr;
    console.log("    Envelope unwrap match:", t1_pass ? "PASS" : "FAIL");

    // Test 2 & 5 & 6: Argon2id works & hashwasm.argon2id is actually invoked & PBKDF2 not used for new pastes
    console.log("\n[2] Testing Argon2id paste creation...");
    argon2idCalls = [];
    pbkdf2Calls = [];

    const key = "test-url-key-32-bytes-long-rnd!";
    const password = "Phase2Password!";
    const message = "Secret text protected by Argon2id in combined/";

    // Paste format: adata = [null]
    const cipherData = await PrivateBin.CryptTool.cipher(key, password, message, [null]);

    const specArray = cipherData[1][0];
    const t5_pass = argon2idCalls.length > 0;
    const t6_pass = pbkdf2Calls.length === 0;
    const t2_spec = specArray[8] === 'argon2id';

    console.log("    hashwasm.argon2id called count:", argon2idCalls.length);
    console.log("    PBKDF2 deriveKey called count:", pbkdf2Calls.length);
    console.log("    specArray[8]:", specArray[8]);
    console.log("    -> hashwasm.argon2id invoked:", t5_pass ? "PASS" : "FAIL");
    console.log("    -> PBKDF2 not silently used for new paste:", t6_pass ? "PASS" : "FAIL");
    console.log("    -> spec[8] === 'argon2id':", t2_spec ? "PASS" : "FAIL");

    // Test 3: Correct password decrypts
    console.log("\n[3] Decrypting Argon2id paste with correct password...");
    argon2idCalls = [];
    pbkdf2Calls = [];
    const decrypted = await PrivateBin.CryptTool.decipher(key, password, cipherData);
    const t3_pass = decrypted === message && argon2idCalls.length > 0 && pbkdf2Calls.length === 0;
    console.log("    Decrypted message:", decrypted);
    console.log("    -> Correct password decrypts:", t3_pass ? "PASS" : "FAIL");

    // Test 4: Wrong password fails
    console.log("\n[4] Decrypting Argon2id paste with wrong password...");
    const wrongDecrypted = await PrivateBin.CryptTool.decipher(key, "WrongPass!", cipherData);
    const t4_pass = wrongDecrypted === "";
    console.log("    Wrong password result (expected empty string):", JSON.stringify(wrongDecrypted));
    console.log("    -> Wrong password fails:", t4_pass ? "PASS" : "FAIL");

    // Test 7: Verify recipient isolation still works
    console.log("\n[7] Testing recipient isolation...");
    const bobKp = await PrivateBin.CryptTool.generateRecipientKeypair();
    const bobPrivB64 = btoa(arraybufferToString(bobKp.privateKeyBytes));

    let t7_pass = false;
    try {
        await PrivateBin.CryptTool.unwrapKeyForRecipient(
            aliceWrap.wrappedKey, aliceWrap.ephemeralPublicKey, bobPrivB64
        );
    } catch(e) {
        t7_pass = true;
    }
    console.log("    Bob key cannot unwrap Alice envelope:", t7_pass ? "PASS" : "FAIL");

    const allPass = t1_pass && t2_spec && t3_pass && t4_pass && t5_pass && t6_pass && t7_pass;
    console.log("\n==========================================");
    console.log("PHASE 2 ARGON2ID INTEGRATION RESULT:", allPass ? "PASS" : "FAIL");
    console.log("==========================================");

    process.exit(allPass ? 0 : 1);
}

runPhase2Tests().catch(e => {
    console.error("Phase 2 test failed with error:", e);
    process.exit(1);
});
