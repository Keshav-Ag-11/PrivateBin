<?php declare(strict_types=1);
use PrivateBin\I18n;
?><!DOCTYPE html>
<html lang="<?php echo I18n::getLanguage(); ?>"<?php echo I18n::isRtl() ? ' dir="rtl"' : ''; ?> data-bs-theme="dark" class="h-100">
	<head>
		<meta charset="utf-8" />
		<meta http-equiv="Content-Security-Policy" content="<?php echo I18n::encode($CSPHEADER); ?>">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<meta name="robots" content="noindex" />
		<meta name="google" content="notranslate">
		<title><?php echo I18n::_($NAME); ?></title>
		<link type="text/css" rel="stylesheet" href="css/bootstrap5/bootstrap<?php echo I18n::isRtl() ? '.rtl' : ''; ?>-5.3.8.css" />
		<link type="text/css" rel="stylesheet" href="css/bootstrap5/privatebin.css?<?php echo rawurlencode($VERSION); ?>" />
<?php
if ($SYNTAXHIGHLIGHTING) :
?>
		<link type="text/css" rel="stylesheet" href="css/prettify/prettify.css?<?php echo rawurlencode($VERSION); ?>" />
<?php
    if (!empty($SYNTAXHIGHLIGHTINGTHEME)) :
?>
		<link type="text/css" rel="stylesheet" href="css/prettify/<?php echo rawurlencode($SYNTAXHIGHLIGHTINGTHEME); ?>.css?<?php echo rawurlencode($VERSION); ?>" />
<?php
    endif;
endif;
?>
		<noscript><link type="text/css" rel="stylesheet" href="css/noscript.css" /></noscript>
		<?php $this->_linkTag('js/zlib-1.3.2.js'); ?>
<?php
if ($QRCODE) :
?>
		<?php $this->_scriptTag('js/kjua-0.10.0.js', 'defer'); ?>
<?php
endif;
?>
		<?php $this->_scriptTag('js/zlib.js', 'defer'); ?>
		<?php $this->_scriptTag('js/base-x-5.0.1.js', 'defer'); ?>
		<?php $this->_scriptTag('js/bootstrap-5.3.8.js', 'defer'); ?>
		<?php $this->_scriptTag('js/dark-mode-switch.js', 'defer'); ?>
<?php
if ($SYNTAXHIGHLIGHTING) :
?>
		<?php $this->_scriptTag('js/prettify.js', 'defer'); ?>
<?php
endif;
if ($MARKDOWN) :
?>
		<?php $this->_scriptTag('js/showdown-2.1.0.js', 'defer'); ?>
<?php
endif;
?>
		<?php $this->_scriptTag('js/purify-3.4.12.js', 'defer'); ?>
		<?php $this->_scriptTag('js/legacy.js', 'defer'); ?>
		<?php $this->_scriptTag('js/argon2.umd.min.js', 'defer'); ?>
		<?php $this->_scriptTag('js/privatebin.js', 'defer'); ?>
		<!-- icon -->
		<link rel="apple-touch-icon" href="<?php echo I18n::encode($BASEPATH); ?>img/apple-touch-icon.png" sizes="180x180" />
		<link rel="icon" type="image/png" href="img/favicon-32x32.png" sizes="32x32" />
		<link rel="icon" type="image/png" href="img/favicon-16x16.png" sizes="16x16" />
		<link rel="manifest" href="manifest.json?<?php echo rawurlencode($VERSION); ?>" />
		<link rel="mask-icon" href="img/safari-pinned-tab.svg" color="#ffcc00" />
		<link rel="shortcut icon" href="img/favicon.ico">
		<meta name="msapplication-config" content="browserconfig.xml">
		<meta name="theme-color" content="#07111a" />
	</head>
	<body role="document" data-bs-theme="dark" data-compression="<?php echo rawurlencode($COMPRESSION); ?>" class="d-flex flex-column h-100">
		
		<!-- Password Decryption Modal -->
		<div id="passwordmodal" tabindex="-1" class="modal fade" role="dialog" aria-hidden="true">
			<div class="modal-dialog modal-dialog-centered" role="document">
				<div class="modal-content sd-card border-cyan">
					<div class="modal-body p-4 text-light">
						<h5 class="modal-title mb-3 d-flex align-items-center gap-2">
							<svg width="20" height="20" fill="currentColor"><use href="img/bootstrap-icons.svg#exclamation-circle" /></svg> <?php echo I18n::_('Please enter the password for this document:') ?>
						</h5>
						<form id="passwordform" role="form">
							<div class="mb-3">
								<div class="input-group">
									<input id="passworddecrypt" type="password" class="form-control input-password" placeholder="<?php echo I18n::_('Enter password') ?>" required="required" />
									<button class="btn btn-outline-secondary toggle-password" type="button">
										<svg width="16" height="16" fill="currentColor"><use href="img/bootstrap-icons.svg#eye" /></svg>
									</button>
								</div>
							</div>
							<button type="submit" class="btn btn-encrypt-cta w-100 py-2">
								<svg width="18" height="18" fill="currentColor"><use href="img/bootstrap-icons.svg#power" /></svg> <?php echo I18n::_('Decrypt') ?>
							</button>
						</form>
					</div>
				</div>
			</div>
		</div>

		<!-- Email Timezone Confirm Modal -->
		<div id="emailconfirmmodal" tabindex="-1" class="modal fade" role="dialog" aria-hidden="true">
			<div class="modal-dialog modal-dialog-centered" role="document">
				<div class="modal-content sd-card border-cyan">
					<div class="modal-body p-4 text-light">
						<h5 class="modal-title mb-3 d-flex align-items-center gap-2">
							<svg width="20" height="20" fill="currentColor"><use href="img/bootstrap-icons.svg#envelope" /></svg> <?php echo I18n::_('Send link via email') ?>
						</h5>
						<p class="text-secondary small mb-4"><?php echo I18n::_('Please select the timezone for the expiry date shown in the email:') ?></p>
						<div class="d-flex gap-3">
							<button id="emailconfirm-timezone-current" type="button" class="btn btn-outline-primary flex-fill">
								<?php echo I18n::_('Local time') ?>
							</button>
							<button id="emailconfirm-timezone-utc" type="button" class="btn btn-outline-secondary flex-fill">
								<?php echo I18n::_('UTC') ?>
							</button>
						</div>
					</div>
				</div>
			</div>
		</div>

		<!-- QR Code Modal -->
		<div id="qrcodemodal" tabindex="-1" class="modal fade" role="dialog" aria-hidden="true">
			<div class="modal-dialog modal-dialog-centered modal-sm" role="document">
				<div class="modal-content sd-card border-cyan">
					<div class="modal-header border-secondary py-2 px-4">
						<h6 class="modal-title d-flex align-items-center gap-2 m-0">
							<svg width="16" height="16" fill="currentColor"><use href="img/bootstrap-icons.svg#qr-code" /></svg> <?php echo I18n::_('Scan QR code') ?>
						</h6>
						<button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
					</div>
					<div class="modal-body p-4 d-flex flex-column align-items-center gap-3">
						<div id="qrcode-display" class="p-2 bg-white rounded-3"></div>
						<div class="small text-secondary text-center"><?php echo I18n::_('Scan to open this document on another device') ?></div>
					</div>
				</div>
			</div>
		</div>

		<!-- Hidden elements required for PrivateBin JS Engine compatibility -->
		<!-- Each element is wrapped individually so JS .parentElement calls don't unhide the entire block -->
		<div id="loadingindicator" class="hidden"><span class="spinner-border spinner-border-sm"></span> <span>Loading...</span></div>
		<div id="noscript" class="hidden"></div>
		<div class="hidden"><ul id="editorTabs"><li role="presentation"><a id="messageedit" href="#">Editor</a></li><li role="presentation"><a id="messagepreview" href="#">Preview</a></li></ul></div>
		<div class="hidden"><input id="messagetab" type="checkbox" checked="checked" /></div>
		<div id="filewrap" class="hidden"><input type="file" id="file" name="file" /></div>
		<div id="opendiscussionoption" class="hidden"><input id="opendiscussion" type="checkbox" /></div>
		<div id="expiration" class="hidden"></div>
		<div id="formatter" class="hidden"></div>
		<div id="customattachment" class="hidden"></div>
		<a href="#" id="fileremovebutton" class="hidden"></a>
		<div id="copyShortcutHint" class="hidden"><span id="copyShortcutHintText"></span><button type="button" id="copyShortcutHintBtn"></button></div>
		<!-- Dark mode toggle (required by dark-mode-switch.js) -->
		<input type="checkbox" id="bd-theme" class="hidden" />
		<!-- Discussion elements (required by DiscussionViewer.init) -->
		<div id="discussion" class="hidden"><div id="commentcontainer"></div></div>

		<!-- Top Navbar (PrivateBin Branding) -->
		<nav class="navbar navbar-expand-lg mb-3">
			<div class="container-fluid max-width-1200">
				<a class="reloadlink navbar-brand" href="">
					<div class="brand-icon-shield">
						<img alt="<?php echo I18n::_($NAME); ?>" src="img/icon.svg" height="24" />
					</div>
					<span><?php echo I18n::_($NAME); ?> <span class="gradient-text fs-6">v<?php echo $VERSION; ?></span></span>
				</a>
				<span class="text-secondary small font-monospace d-none d-md-inline ms-auto me-3">ZERO-KNOWLEDGE ENCRYPTED PASTEBIN</span>
				
				<div class="d-flex align-items-center gap-2">
					<button id="retrybutton" type="button" class="reloadlink hidden btn btn-primary btn-sm">
						<svg width="14" height="14" fill="currentColor"><use href="img/bootstrap-icons.svg#repeat" /></svg> <?php echo I18n::_('Retry'); ?>
					</button>
					<button id="newbutton" type="button" class="hidden btn btn-outline-primary btn-sm">
						<svg width="14" height="14" fill="currentColor"><use href="img/bootstrap-icons.svg#file-earmark" /></svg> <?php echo I18n::_('New'); ?>
					</button>
					<button id="clonebutton" type="button" class="hidden btn btn-outline-secondary btn-sm">
						<svg width="14" height="14" fill="currentColor"><use href="img/bootstrap-icons.svg#copy" /></svg> <?php echo I18n::_('Clone'); ?>
					</button>
					<button id="rawtextbutton" type="button" class="hidden btn btn-outline-secondary btn-sm">
						<svg width="14" height="14" fill="currentColor"><use href="img/bootstrap-icons.svg#filetype-txt" /></svg> <?php echo I18n::_('Raw text'); ?>
					</button>
					<button id="downloadtextbutton" type="button" class="hidden btn btn-outline-secondary btn-sm">
						<svg width="14" height="14" fill="currentColor"><use href="img/bootstrap-icons.svg#download" /></svg> <?php echo I18n::_('Save document'); ?>
					</button>
					<button id="qrcodelink" type="button" class="hidden btn btn-outline-secondary btn-sm">
						<svg width="14" height="14" fill="currentColor"><use href="img/bootstrap-icons.svg#qr-code" /></svg>
					</button>
					<button id="emaillink" type="button" class="hidden btn btn-outline-secondary btn-sm">
						<svg width="14" height="14" fill="currentColor"><use href="img/bootstrap-icons.svg#envelope" /></svg>
					</button>
				</div>
			</div>
		</nav>

		<main class="flex-shrink-0">
			<div class="container max-width-1200 py-2">

				<!-- Hero Section -->
				<div class="hero-section mb-4">
					<div class="inline-pill mb-3">
						<span class="dot-green"></span> PRIVATE &nbsp;•&nbsp; ENCRYPTED &nbsp;•&nbsp; TEMPORARY
					</div>
					<h1 class="hero-title mb-3">Your data should belong to <span class="gradient-text">you.</span></h1>
					<p class="hero-subtitle mb-4">
						PrivateBin encrypts every paste and file inside your browser before it ever leaves the tab. Share a link, set it to self-destruct, and leave nothing behind.
					</p>
				</div>

				<!-- Zero-Knowledge Interactive Pipeline Component -->
				<div class="pipeline-container mb-5">
					<div class="pipeline-header d-flex justify-content-between align-items-center mb-3">
						<span>ZERO-KNOWLEDGE PIPELINE</span>
						<span class="pulse-live"><span class="pulse-dot"></span> LIVE ENCRYPTION ENGINE</span>
					</div>
					<div class="row g-3">
						<!-- Stage 1: Plaintext -->
						<div class="col-md-3">
							<div class="pipe-card active">
								<div>
									<div class="pipe-icon">
										<svg width="20" height="20" fill="currentColor"><use href="img/bootstrap-icons.svg#file-earmark" /></svg>
									</div>
									<div class="pipe-title">Plaintext</div>
									<div class="pipe-desc">Your note or file, in your browser tab</div>
								</div>

							</div>
						</div>
						<!-- Stage 2: Browser Encryption -->
						<div class="col-md-3">
							<div class="pipe-card active">
								<div>
									<div class="pipe-icon">
										<svg width="20" height="20" fill="currentColor"><use href="img/bootstrap-icons.svg#cloud-upload" /></svg>
									</div>
									<div class="pipe-title">Browser Encryption</div>
									<div class="pipe-desc">AES-256-GCM + Argon2id (19 MB), key never leaves tab</div>
								</div>

							</div>
						</div>
						<!-- Stage 3: Encrypted Storage -->
						<div class="col-md-3">
							<div class="pipe-card active">
								<div>
									<div class="pipe-icon">
										<svg width="20" height="20" fill="currentColor"><use href="img/bootstrap-icons.svg#cloud-download" /></svg>
									</div>
									<div class="pipe-title">Encrypted Storage</div>
									<div class="pipe-desc">We only ever hold zero-knowledge ciphertext</div>
								</div>

							</div>
						</div>
						<!-- Stage 4: Secure Decryption -->
						<div class="col-md-3">
							<div class="pipe-card active">
								<div>
									<div class="pipe-icon">
										<svg width="20" height="20" fill="currentColor"><use href="img/bootstrap-icons.svg#eye" /></svg>
									</div>
									<div class="pipe-title">Secure Decryption</div>
									<div class="pipe-desc">Unlocked with fragment key or recipient envelope</div>
								</div>

							</div>
						</div>
					</div>
				</div>

				<!-- Alerts & Notifications -->
				<div id="remainingtime" role="alert" class="hidden alert alert-info sd-card mb-3">
					<svg width="16" height="16" fill="currentColor"><use href="img/bootstrap-icons.svg#fire" /></svg>
				</div>
				<div id="status" role="alert" class="alert alert-info sd-card mb-3 <?php echo empty($STATUS) ? ' hidden' : '' ?>">
					<?php echo I18n::encode($STATUS); ?>
				</div>
				<div id="errormessage" role="alert" class="<?php echo empty($ERROR) ? 'hidden' : '' ?> alert alert-danger sd-card mb-3">
					<svg width="16" height="16" fill="currentColor"><use href="img/bootstrap-icons.svg#exclamation-triangle" /></svg> <?php echo I18n::encode($ERROR); ?>
				</div>

				<!-- Paste Created Success Card -->
				<div id="pastesuccess" class="sd-card p-4 mb-4 hidden">
					<div class="d-flex justify-content-between align-items-center mb-3">
						<h5 class="m-0 text-cyan">
							<svg width="20" height="20" fill="currentColor"><use href="img/bootstrap-icons.svg#check" /></svg> Paste Encrypted & Created!
						</h5>
						<a href="#" id="deletelink" class="btn btn-outline-danger btn-sm">
							<svg width="14" height="14" fill="currentColor"><use href="img/bootstrap-icons.svg#trash" /></svg> Delete Document
						</a>
					</div>
					
					<!-- Share Link Box -->
					<div class="p-3 rounded-3 bg-dark border border-secondary mb-3 d-flex align-items-center gap-3">
						<div class="flex-grow-1">
							<div class="small text-muted font-monospace mb-1">
								<svg width="14" height="14" fill="currentColor"><use href="img/bootstrap-icons.svg#qr-code" /></svg> SHARE LINK
							</div>
							<div id="pastelink" class="font-monospace text-cyan fw-bold text-break fs-6"></div>
							<div class="small text-secondary mt-1">Everything after # stays in the browser — it is never sent to the server.</div>
						</div>
						<button id="copyLink" type="button" class="btn btn-outline-primary btn-sm text-nowrap px-3">
							<svg width="14" height="14" fill="currentColor"><use href="img/bootstrap-icons.svg#copy" /></svg> Copy link
						</button>
					</div>

					<!-- Recipient Envelope Links -->
					<div id="recipientlinks" class="hidden mt-3">
						<div class="alert alert-info sd-card small mb-3">
							<svg width="16" height="16" fill="currentColor"><use href="img/bootstrap-icons.svg#envelope" /></svg> <strong>Envelope Encryption Enabled:</strong> Each recipient has a unique link below.
						</div>
						<div id="recipientlinkcontainer" class="d-flex flex-column gap-2"></div>
						<!-- Hidden template card for each recipient's individual link -->
						<div id="recipientlinktemplate" class="hidden sd-card p-3 d-flex align-items-center gap-3" style="border:1px solid rgba(99,179,237,0.25);">
							<svg width="20" height="20" fill="currentColor" class="text-cyan flex-shrink-0"><use href="img/bootstrap-icons.svg#person-circle" /></svg>
							<div class="flex-grow-1 overflow-hidden">
								<div class="small fw-bold text-cyan recipient-link-name mb-1">Recipient</div>
								<input type="text" class="form-control form-control-sm font-monospace recipient-link-url bg-dark text-light border-secondary" readonly style="font-size:0.7rem;" />
							</div>
							<button class="btn btn-sm btn-outline-cyan recipient-link-copy flex-shrink-0" title="Copy link">
								<svg width="14" height="14" fill="currentColor"><use href="img/bootstrap-icons.svg#copy" /></svg>
							</button>
						</div>
					</div>
				</div>

				<!-- Main View Container for Displaying Decrypted Pastes -->
				<article id="view-section" class="mb-4">
					<div id="placeholder" class="col-md-12 hidden alert alert-secondary"><?php echo I18n::_('+++ no document text +++'); ?></div>
					<div id="attachmentPreview" class="col-md-12 text-center hidden mb-3"></div>
					<div id="prettymessage" class="hidden mb-4">
						<div class="small font-monospace text-cyan mb-3 d-flex align-items-center gap-2">
							<svg width="16" height="16" fill="currentColor"><use href="img/bootstrap-icons.svg#file-earmark" /></svg> Decrypted Document Content
						</div>
						<pre id="prettyprint" class="prettyprint linenums:1"></pre>
					</div>
					<div id="plaintext" class="font-monospace hidden mb-4"></div>
				</article>

				<!-- Main Editor & Protection Grid -->
				<div id="editor-section" class="row g-4 mb-5">
					
					<!-- Left Column: Content Editor -->
					<div class="col-lg-8">
						<div class="sd-card p-3 h-100 d-flex flex-column">
							<div class="d-flex justify-content-between align-items-center mb-3">
								<div class="d-flex align-items-center gap-2">
									<svg width="18" height="18" fill="currentColor" class="text-cyan"><use href="img/bootstrap-icons.svg#file-earmark" /></svg>
									<span class="fw-bold">Document Content</span>
								</div>
								<div class="d-flex gap-2">
									<select id="pasteFormatter" name="pasteFormatter" class="form-select form-select-sm bg-dark text-light border-secondary">
										<?php foreach ($FORMATTER as $key => $value) : ?>
										<option value="<?php echo $key; ?>"<?php if ($key === $FORMATTERDEFAULT) : ?> selected="selected"<?php endif; ?>><?php echo $value; ?></option>
										<?php endforeach; ?>
									</select>
								</div>
							</div>
							<div class="flex-grow-1">
								<textarea id="message" name="message" class="form-control h-100" rows="12" placeholder="# Write or paste your note, code, or secret environment variables here..."></textarea>
							</div>

							<!-- File Attachment Dropzone -->
							<?php if ($FILEUPLOAD) : ?>
							<div class="mt-3 p-3 border border-secondary border-dashed rounded-3 bg-dark text-center">
								<div id="dragAndDropFileName" class="small text-muted">
									Drag & drop a file or paste an image from clipboard
								</div>
								<div id="attachment" class="hidden mt-2"></div>
							</div>
							<?php endif; ?>
						</div>
					</div>

					<!-- Right Column: Protection Sidebar -->
					<div class="col-lg-4">
						<div class="sd-card p-4 h-100 d-flex flex-column">
							
							<div class="small font-monospace text-muted mb-3">
								<svg width="14" height="14" fill="currentColor" class="text-cyan"><use href="img/bootstrap-icons.svg#exclamation-circle" /></svg> PROTECTION
							</div>

							<!-- Security Mode Buttons -->
							<div class="d-flex gap-1 p-1 bg-dark rounded-3 mb-3 border border-secondary">
								<button type="button" class="btn btn-sm btn-dark flex-fill active" id="btn-mode-standard">Standard</button>
								<button type="button" class="btn btn-sm btn-dark flex-fill" id="toggledecoy">🎭 Decoy</button>
								<button type="button" class="btn btn-sm btn-dark flex-fill" id="togglerecipients">👥 Envelopes</button>
							</div>

							<!-- Password Protection Field -->
							<div id="password" class="mb-3">
								<label for="passwordinput" class="form-label small fw-semibold">Password Protection</label>
								<div class="input-group">
									<input type="password" id="passwordinput" class="form-control" placeholder="Optional second factor" />
									<button class="btn btn-outline-secondary toggle-password" type="button">
										<svg width="16" height="16" fill="currentColor"><use href="img/bootstrap-icons.svg#eye" /></svg>
									</button>
								</div>
								<div class="small text-cyan mt-1 font-monospace" style="font-size: 0.75rem;">
									Argon2id KDF Active
								</div>
							</div>

							<!-- Decoy Password Container -->
							<div id="decoysection" class="p-3 rounded-3 bg-dark border border-warning mb-3 hidden">
								<div class="small fw-bold text-warning mb-2">🎭 Decoy Password (Deniable)</div>
								<div class="mb-2">
									<input type="password" id="decoypasswordinput" class="form-control form-control-sm" placeholder="Decoy password..." />
								</div>
								<div>
									<textarea id="decoytextinput" class="form-control form-control-sm font-monospace" rows="3" placeholder="Decoy cover story text..."></textarea>
								</div>
							</div>

							<!-- Recipient Envelope Container -->
							<div id="recipientssection" class="p-3 rounded-3 bg-dark border border-info mb-3 hidden">
								<div class="small fw-bold text-info mb-2">👥 Recipient Envelopes</div>
								<div id="recipientlist" class="mb-2"></div>
								<button type="button" id="addrecipient" class="btn btn-outline-info btn-sm w-100">
									+ Add Recipient Key
								</button>
							</div>

							<!-- Expiration Pill Selector -->
							<div class="mb-3">
								<label class="form-label small fw-semibold">Expires in</label>
								<div class="expire-pill-group">
									<button type="button" class="btn btn-expire-pill" data-expire="5min">5 min</button>
									<button type="button" class="btn btn-expire-pill" data-expire="1hour">1 hour</button>
									<button type="button" class="btn btn-expire-pill active" data-expire="1day">24 hours</button>
									<button type="button" class="btn btn-expire-pill" data-expire="1week">7 days</button>
								</div>
								<!-- Hidden Native Select for PrivateBin JS compatibility -->
								<select id="pasteExpiration" name="pasteExpiration" class="hidden">
									<?php foreach ($EXPIRE as $key => $value) : ?>
									<option value="<?php echo $key; ?>"<?php if ($key === $EXPIREDEFAULT) : ?> selected="selected"<?php endif; ?>><?php echo $value; ?></option>
									<?php endforeach; ?>
								</select>
							</div>

							<!-- Burn After Reading Switch -->
							<div id="burnafterreadingoption" class="form-check form-switch p-3 bg-dark rounded-3 border border-secondary mb-4 d-flex justify-content-between align-items-center">
								<label class="form-check-label small fw-semibold mb-0" for="burnafterreading">
									<svg width="14" height="14" fill="currentColor" class="text-danger"><use href="img/bootstrap-icons.svg#fire" /></svg> Burn after reading
								</label>
								<input class="form-check-input ms-0" type="checkbox" id="burnafterreading" name="burnafterreading" <?php if ($BURNAFTERREADINGSELECTED) : ?> checked="checked"<?php endif; ?> />
							</div>

							<!-- Action CTA Button -->
							<button type="button" id="sendbutton" class="btn btn-encrypt-cta mt-auto">
								<svg width="18" height="18" fill="currentColor"><use href="img/bootstrap-icons.svg#send" /></svg> Encrypt & create link
							</button>

						</div>
					</div>

				</div>

			</div>
		</main>

		<!-- Expiration Pill Sync Script -->
		<script>
			document.addEventListener('DOMContentLoaded', function() {
				const pills = document.querySelectorAll('.btn-expire-pill');
				const nativeSelect = document.getElementById('pasteExpiration');
				if (pills && nativeSelect) {
					pills.forEach(pill => {
						pill.addEventListener('click', function() {
							pills.forEach(p => p.classList.remove('active'));
							this.classList.add('active');
							const val = this.getAttribute('data-expire');
							if (val && nativeSelect) {
								nativeSelect.value = val;
								// Trigger change event for PrivateBin JS listener
								nativeSelect.dispatchEvent(new Event('change'));
							}
						});
					});
				}
			});
		</script>
	</body>
</html>
