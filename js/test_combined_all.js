/**
 * test_combined_all.js
 * Comprehensive Test Matrix for Combined Features (Phase 5, Phase 6, Phase 7)
 */

const fs = require('fs');
const path = require('path');

// 1. Setup jsdom & webcrypto
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

// 3. Load global dependencies
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

async function runComprehensiveMatrix() {
    console.log("==========================================================");
    console.log("RUNNING COMPREHENSIVE COMBINED FEATURE TEST MATRIX");
    console.log("==========================================================\n");

    let passCount = 0;
    let failCount = 0;

    function assertTest(name, condition, details = "") {
        if (condition) {
            console.log(`  [PASS] ${name} ${details}`);
            passCount++;
        } else {
            console.error(`  [FAIL] ${name} ${details}`);
            failCount++;
        }
    }

    // --- SETUP: Create multi-recipient envelope paste with Decoy protection ---
    const REAL_CONTENT = "CONFIDENTIAL: Alice, Bob, and Mary's shared real document.";
    const DECOY_CONTENT = "PUBLIC: Harmless decoy recipe list for unexpected viewers.";

    const REAL_PASS = "SuperSecretRealPass123!";
    const DECOY_PASS = "HarmlessDecoyPass456!";
    const WRONG_PASS = "WrongPassword789!";

    // 1. Generate keypairs for Alice, Bob, Mary
    const aliceKp = await PrivateBin.CryptTool.generateRecipientKeypair();
    const bobKp = await PrivateBin.CryptTool.generateRecipientKeypair();
    const maryKp = await PrivateBin.CryptTool.generateRecipientKeypair();

    const alicePrivB64 = btoa(arraybufferToString(aliceKp.privateKeyBytes));
    const bobPrivB64 = btoa(arraybufferToString(bobKp.privateKeyBytes));
    const maryPrivB64 = btoa(arraybufferToString(maryKp.privateKeyBytes));

    const alicePubStr = arraybufferToString(aliceKp.publicKeyBytes);
    const bobPubStr = arraybufferToString(bobKp.publicKeyBytes);
    const maryPubStr = arraybufferToString(maryKp.publicKeyBytes);

    // 2. Generate 32-byte CEK and create Decoy Blob
    const masterCEK = arraybufferToString(window.crypto.getRandomValues(new Uint8Array(32)));

    argon2idCalls = [];
    pbkdf2Calls = [];

    const duressBlob = await PrivateBin.CryptTool.encryptDuressPaste(
        REAL_CONTENT, REAL_PASS, DECOY_CONTENT, DECOY_PASS, masterCEK
    );

    // 3. Wrap CEK for each recipient
    const aliceWrap = await PrivateBin.CryptTool.wrapKeyForRecipient(masterCEK, alicePubStr);
    const bobWrap = await PrivateBin.CryptTool.wrapKeyForRecipient(masterCEK, bobPubStr);
    const maryWrap = await PrivateBin.CryptTool.wrapKeyForRecipient(masterCEK, maryPubStr);

    const recipientsArray = [
        { wrappedKey: aliceWrap.wrappedKey, ephemeralPublicKey: aliceWrap.ephemeralPublicKey },
        { wrappedKey: bobWrap.wrappedKey, ephemeralPublicKey: bobWrap.ephemeralPublicKey },
        { wrappedKey: maryWrap.wrappedKey, ephemeralPublicKey: maryWrap.ephemeralPublicKey }
    ];

    console.log("--- PHASE 5 & 6: RECIPIENT & DECOY MATRIX TESTS ---");

    // Helper to unwrap CEK and decrypt decoy blob for a given recipient
    async function testRecipientDecrypt(privKeyB64, password, customRecipients = recipientsArray, customBlob = duressBlob) {
        let unwrappedCEK = null;
        for (const env of customRecipients) {
            try {
                unwrappedCEK = await PrivateBin.CryptTool.unwrapKeyForRecipient(
                    env.wrappedKey, env.ephemeralPublicKey, privKeyB64
                );
                break;
            } catch (e) {}
        }
        if (!unwrappedCEK) {
            return { error: 'UNWRAP_FAILED', content: null };
        }
        try {
            const content = await PrivateBin.CryptTool.decryptDuressBlob(customBlob, password, unwrappedCEK);
            return { error: null, content: content };
        } catch (e) {
            return { error: e.message || 'DECRYPT_FAILED', content: null };
        }
    }

    // 1-3. Alice Matrix
    const aliceReal = await testRecipientDecrypt(alicePrivB64, REAL_PASS);
    assertTest("1. Alice + Real Password", aliceReal.content === REAL_CONTENT);

    const aliceDecoy = await testRecipientDecrypt(alicePrivB64, DECOY_PASS);
    assertTest("2. Alice + Decoy Password", aliceDecoy.content === DECOY_CONTENT);

    const aliceWrong = await testRecipientDecrypt(alicePrivB64, WRONG_PASS);
    assertTest("3. Alice + Wrong Password", aliceWrong.error === 'Incorrect password');

    // 4-6. Bob Matrix
    const bobReal = await testRecipientDecrypt(bobPrivB64, REAL_PASS);
    assertTest("4. Bob + Real Password", bobReal.content === REAL_CONTENT);

    const bobDecoy = await testRecipientDecrypt(bobPrivB64, DECOY_PASS);
    assertTest("5. Bob + Decoy Password", bobDecoy.content === DECOY_CONTENT);

    const bobWrong = await testRecipientDecrypt(bobPrivB64, WRONG_PASS);
    assertTest("6. Bob + Wrong Password", bobWrong.error === 'Incorrect password');

    // 7-9. Mary Matrix
    const maryReal = await testRecipientDecrypt(maryPrivB64, REAL_PASS);
    assertTest("7. Mary + Real Password", maryReal.content === REAL_CONTENT);

    const maryDecoy = await testRecipientDecrypt(maryPrivB64, DECOY_PASS);
    assertTest("8. Mary + Decoy Password", maryDecoy.content === DECOY_CONTENT);

    const maryWrong = await testRecipientDecrypt(maryPrivB64, WRONG_PASS);
    assertTest("9. Mary + Wrong Password", maryWrong.error === 'Incorrect password');

    // 10-12. Cross-recipient Tests (Recipient URL vs other recipient envelopes)
    // Alice URL fragment vs only Bob's envelope entry
    const aliceVsBobEnv = await testRecipientDecrypt(alicePrivB64, REAL_PASS, [recipientsArray[1]]);
    assertTest("10. Alice URL cannot decrypt Bob envelope", aliceVsBobEnv.error === 'UNWRAP_FAILED');

    // Bob URL fragment vs only Alice's envelope entry
    const bobVsAliceEnv = await testRecipientDecrypt(bobPrivB64, REAL_PASS, [recipientsArray[0]]);
    assertTest("11. Bob URL cannot decrypt Alice envelope", bobVsAliceEnv.error === 'UNWRAP_FAILED');

    // Mary URL fragment vs only Alice's envelope entry
    const maryVsAliceEnv = await testRecipientDecrypt(maryPrivB64, REAL_PASS, [recipientsArray[0]]);
    assertTest("12. Mary URL cannot decrypt Alice envelope", maryVsAliceEnv.error === 'UNWRAP_FAILED');

    // 13-15. Malformed URL Tests
    // 13. Truncated URL fragment
    const truncatedPrivB64 = alicePrivB64.substring(0, alicePrivB64.length - 15);
    const truncRes = await testRecipientDecrypt(truncatedPrivB64, REAL_PASS);
    assertTest("13. Truncated URL fragment fails safely", truncRes.error === 'UNWRAP_FAILED');

    // 14. Corrupted URL fragment
    const corruptPrivB64 = btoa("invalid-jwk-json-bytes-here");
    const corruptRes = await testRecipientDecrypt(corruptPrivB64, REAL_PASS);
    assertTest("14. Corrupted URL fragment fails safely", corruptRes.error === 'UNWRAP_FAILED');

    // 15. Missing URL fragment
    const missingRes = await testRecipientDecrypt("", REAL_PASS);
    assertTest("15. Missing URL fragment fails safely", missingRes.error === 'UNWRAP_FAILED');

    // 16. Corrupted Ciphertext Test
    const corruptedBlob = JSON.parse(JSON.stringify(duressBlob));
    corruptedBlob.layers[0].ciphertext = btoa("corrupted-ciphertext-data-12345");
    corruptedBlob.layers[1].ciphertext = btoa("corrupted-ciphertext-data-67890");
    const corruptCtRes = await testRecipientDecrypt(alicePrivB64, REAL_PASS, recipientsArray, corruptedBlob);
    assertTest("16. Corrupted ciphertext fails safely", corruptCtRes.error === 'Incorrect password');

    // 17. Verify Argon2id was actually invoked during paste creation and decryption
    assertTest("17. Verify hashwasm.argon2id is actually invoked", argon2idCalls.length > 0, `(Count: ${argon2idCalls.length})`);

    // 18. Verify PBKDF2 was NOT accidentally used for NEW Argon2id pastes
    assertTest("18. Verify PBKDF2 is NOT used for new pastes", pbkdf2Calls.length === 0, `(Count: ${pbkdf2Calls.length})`);


    console.log("\n--- PHASE 7: REGRESSION TESTS (BASE PRIVATEBIN) ---");

    // R1: Standard single-key Argon2id paste creation and decryption
    argon2idCalls = [];
    pbkdf2Calls = [];
    const stdKey = "standard-32-byte-url-key-rnd!";
    const stdPass = "StandardPass123!";
    const stdMsg = "Hello Standard PrivateBin Paste";

    const stdCipher = await PrivateBin.CryptTool.cipher(stdKey, stdPass, stdMsg, [null]);
    assertTest("R1. Standard Argon2id paste creation", stdCipher[1][0][8] === 'argon2id');

    const stdPlain = await PrivateBin.CryptTool.decipher(stdKey, stdPass, stdCipher);
    assertTest("R2. Standard Argon2id paste decryption", stdPlain === stdMsg);

    // R3: PBKDF2 legacy fallback paste decryption
    argon2idCalls = [];
    pbkdf2Calls = [];
    const legacyCipher = JSON.parse(JSON.stringify(stdCipher));
    legacyCipher[1][0].pop(); // remove 'argon2id' marker -> spec[8] is undefined

    await PrivateBin.CryptTool.decipher(stdKey, stdPass, legacyCipher);
    assertTest("R3. Legacy PBKDF2 paste fallback path", argon2idCalls.length === 0 && pbkdf2Calls.length > 0);


    console.log("\n==========================================");
    console.log(`FINAL COMPREHENSIVE TEST RESULT: ${failCount === 0 ? "ALL PASSED" : "FAILED"}`);
    console.log(`Passed: ${passCount}, Failed: ${failCount}`);
    console.log("==========================================");

    process.exit(failCount === 0 ? 0 : 1);
}

runComprehensiveMatrix().catch(e => {
    console.error("Test execution failed:", e);
    process.exit(1);
});
