import './bootstrap';
import flatpickr from 'flatpickr';
import 'flatpickr/dist/flatpickr.min.css';

const onReady = (callback) => {
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', callback, { once: true });
		return;
	}

	callback();
};

const initializeNavbarScrollState = () => {
	const navbars = document.querySelectorAll('.navbar-modern');
	if (!navbars.length) {
		return;
	}

	const syncState = () => {
		const isScrolled = window.scrollY > 12;
		navbars.forEach((navbar) => navbar.classList.toggle('is-scrolled', isScrolled));
	};

	syncState();
	window.addEventListener('scroll', syncState, { passive: true });
};

const initializeRevealAnimations = () => {
	const targets = Array.from(
		document.querySelectorAll(
			'.page-header-shell, .card, .glass-card, .table-responsive, .alert, .hero-section .badge, .hero-section h1, .hero-section p, .hero-section .btn'
		)
	);

	if (!targets.length) {
		return;
	}

	targets.forEach((target, index) => {
		if (!target.hasAttribute('data-reveal')) {
			target.setAttribute('data-reveal', '');
		}

		target.style.setProperty('--reveal-delay', `${Math.min(index * 42, 320)}ms`);
	});

	if (window.matchMedia('(prefers-reduced-motion: reduce)').matches || !('IntersectionObserver' in window)) {
		targets.forEach((target) => target.classList.add('is-visible'));
		return;
	}

	const observer = new IntersectionObserver(
		(entries) => {
			entries.forEach((entry) => {
				if (!entry.isIntersecting) {
					return;
				}

				entry.target.classList.add('is-visible');
				observer.unobserve(entry.target);
			});
		},
		{
			threshold: 0.12,
			rootMargin: '0px 0px -8% 0px',
		}
	);

	targets.forEach((target) => observer.observe(target));
};

const initializeImagePreview = () => {
	const imageInput = document.getElementById('image-input');
	const previewDiv = document.getElementById('image-preview');
	const previewImg = document.getElementById('preview-img');
	const ratioWarning = document.getElementById('image-ratio-warning');
	const ratioHint = document.getElementById('image-ratio-hint');

	if (!imageInput || !previewDiv || !previewImg) {
		return;
	}

	imageInput.addEventListener('change', (event) => {
		const file = event.target.files[0];
		if (file && file.type.startsWith('image/')) {
			const reader = new FileReader();
			reader.onload = (e) => {
				previewImg.src = e.target.result;
				previewDiv.style.display = 'block';

				const testImage = new Image();
				testImage.onload = () => {
					const ratio = testImage.width / testImage.height;
					const isSixteenByNine = Math.abs(ratio - 16 / 9) <= 0.01;

					if (ratioWarning) {
						ratioWarning.style.display = isSixteenByNine ? 'none' : 'block';
					}

					if (ratioHint) {
						ratioHint.textContent = `Selected image: ${testImage.width}x${testImage.height}${isSixteenByNine ? ' (valid 16:9)' : ' (not 16:9)'}`;
					}
				};

				testImage.src = e.target.result;
			};
			reader.readAsDataURL(file);
		} else {
			previewDiv.style.display = 'none';
			if (ratioWarning) {
				ratioWarning.style.display = 'none';
			}
			if (ratioHint) {
				ratioHint.textContent = 'Recommended example: 1600x900, 1280x720, or 1920x1080.';
			}
		}
	});
};

const initializeImageLightbox = () => {
	const lightbox = document.getElementById('image-lightbox');
	const lightboxImage = document.getElementById('image-lightbox-image');
	const lightboxCaption = document.getElementById('image-lightbox-title');
	const triggers = document.querySelectorAll('[data-lightbox-image], [data-lightbox-src]');

	if (!lightbox || !lightboxImage || !lightboxCaption || !triggers.length) {
		return;
	}

	let lastActiveElement = null;

	const closeLightbox = () => {
		lightbox.classList.remove('is-open');
		lightbox.setAttribute('aria-hidden', 'true');
		document.body.classList.remove('lightbox-open');
		lightboxImage.src = '';
		lightboxImage.alt = '';
		lightboxCaption.textContent = '';

		if (lastActiveElement instanceof HTMLElement) {
			lastActiveElement.focus();
		}
	};

	const openLightbox = (trigger) => {
		lastActiveElement = trigger;
		const source = trigger.getAttribute('data-lightbox-src') ?? trigger.getAttribute('src') ?? '';
		const alt = trigger.getAttribute('data-lightbox-alt') ?? trigger.getAttribute('alt') ?? 'Image preview';
		lightboxImage.src = source;
		lightboxImage.alt = alt;
		lightboxCaption.textContent = alt;
		lightbox.classList.add('is-open');
		lightbox.setAttribute('aria-hidden', 'false');
		document.body.classList.add('lightbox-open');

		const closeButton = lightbox.querySelector('[data-lightbox-close]');
		if (closeButton instanceof HTMLElement) {
			closeButton.focus();
		}
	};

	triggers.forEach((trigger) => {
		trigger.addEventListener('click', (event) => {
			event.preventDefault();
			event.stopPropagation();
			openLightbox(trigger);
		});

		trigger.addEventListener('keydown', (event) => {
			if (event.key !== 'Enter' && event.key !== ' ') {
				return;
			}

			event.preventDefault();
			openLightbox(trigger);
		});
	});

	lightbox.querySelectorAll('[data-lightbox-close]').forEach((element) => {
		element.addEventListener('click', closeLightbox);
	});

	document.addEventListener('keydown', (event) => {
		if (!lightbox.classList.contains('is-open')) {
			return;
		}

		if (event.key === 'Escape') {
			closeLightbox();
		}
	});
};

const initializeCountdowns = () => {
	const countdowns = Array.from(document.querySelectorAll('[data-countdown-to]'));

	if (!countdowns.length) {
		return;
	}

	const formatCountdown = (target) => {
		const diffMs = target.getTime() - Date.now();

		if (diffMs <= 0) {
			return 'expired';
		}

		const totalSeconds = Math.floor(diffMs / 1000);
		const days = Math.floor(totalSeconds / 86400);
		const hours = Math.floor((totalSeconds % 86400) / 3600);
		const minutes = Math.floor((totalSeconds % 3600) / 60);
		const seconds = totalSeconds % 60;

		if (days > 0) {
			return `${days}d ${hours}h`;
		}

		if (hours > 0) {
			return `${hours}h ${minutes}m ${seconds}s`;
		}

		if (minutes > 0) {
			return `${minutes}m ${seconds}s`;
		}

		return `${seconds}s`;
	};

	const syncCountdowns = () => {
		countdowns.forEach((countdown) => {
			const rawTarget = countdown.getAttribute('data-countdown-to');
			const label = countdown.querySelector('[data-countdown-label]');

			if (!rawTarget || !(label instanceof HTMLElement)) {
				return;
			}

			const target = new Date(rawTarget);

			if (Number.isNaN(target.getTime())) {
				label.textContent = 'date unavailable';
				return;
			}

			const secondsLeft = Math.floor((target.getTime() - Date.now()) / 1000);
			const urgencyMinutes = Number(countdown.getAttribute('data-countdown-urgent-minutes') ?? '60');

			countdown.classList.toggle('is-urgent', secondsLeft > 0 && secondsLeft <= urgencyMinutes * 60);
			countdown.classList.toggle('is-expired', secondsLeft <= 0);

			label.textContent = formatCountdown(target);
		});
	};

	const useLiveSecondTick = countdowns.some((countdown) => countdown.getAttribute('data-countdown-mode') === 'live-second');

	syncCountdowns();
	window.setInterval(syncCountdowns, useLiveSecondTick ? 1000 : 30000);
};

const initializeFilterForms = () => {
	const forms = Array.from(document.querySelectorAll('[data-filter-form]'));

	if (!forms.length) {
		return;
	}

	forms.forEach((form) => {
		const liveSearchInput = form.querySelector('[data-live-search="true"]');

		if (!(liveSearchInput instanceof HTMLInputElement)) {
			return;
		}

		let timeoutId;
		const delay = Number(liveSearchInput.getAttribute('data-live-search-delay') ?? '350');

		liveSearchInput.addEventListener('input', () => {
			window.clearTimeout(timeoutId);
			timeoutId = window.setTimeout(() => {
				form.requestSubmit();
			}, Number.isFinite(delay) ? delay : 350);
		});
	});
};

const initializeDatePickers = () => {
	const dateFields = Array.from(document.querySelectorAll('[data-flatpickr-date]'));

	if (!dateFields.length) {
		return;
	}

	dateFields.forEach((field) => {
		if (!(field instanceof HTMLInputElement)) {
			return;
		}

		const minDate = field.getAttribute('data-min-date') ?? undefined;

		flatpickr(field, {
			dateFormat: 'Y-m-d',
			altInput: true,
			altFormat: 'F j, Y',
			minDate,
			disableMobile: true,
			allowInput: false,
		});
	});
};

const initializeOptionDropdowns = () => {
	const dropdowns = Array.from(document.querySelectorAll('[data-option-dropdown]'));

	if (!dropdowns.length) {
		return;
	}

	dropdowns.forEach((dropdown) => {
		const input = dropdown.querySelector('[data-option-input]');
		const label = dropdown.querySelector('[data-option-label]');
		const options = Array.from(dropdown.querySelectorAll('[data-option-value]'));

		if (!(input instanceof HTMLInputElement) || !(label instanceof HTMLElement) || !options.length) {
			return;
		}

		const labelsByValue = new Map(
			options.map((option) => [
				option.getAttribute('data-option-value') ?? '',
				option.getAttribute('data-option-text') ?? option.textContent?.trim() ?? 'Select option',
			])
		);

		const syncSelectedLabel = () => {
			const selectedLabel = labelsByValue.get(input.value) ?? 'Select option';
			label.textContent = selectedLabel;
		};

		syncSelectedLabel();

		options.forEach((option) => {
			option.addEventListener('click', () => {
				const value = option.getAttribute('data-option-value');
				if (value === null) {
					return;
				}

				input.value = value;
				syncSelectedLabel();
			});
		});
	});
};

const createActionConfirmationModal = () => {
	const modal = document.createElement('div');
	modal.className = 'action-confirmation-modal';
	modal.setAttribute('aria-hidden', 'true');
	modal.innerHTML = `
		<div class="action-confirmation-backdrop" data-confirm-cancel></div>
		<div class="action-confirmation-dialog" role="dialog" aria-modal="true" aria-labelledby="action-confirmation-title" aria-describedby="action-confirmation-message">
			<div class="action-confirmation-icon" data-confirm-icon><i class="bi bi-question-lg"></i></div>
			<div class="action-confirmation-copy">
				<h5 id="action-confirmation-title">Confirm action</h5>
				<p id="action-confirmation-message">Continue with this action?</p>
			</div>
			<div class="action-confirmation-actions">
				<button type="button" class="btn btn-outline-secondary rounded-pill" data-confirm-cancel>Cancel</button>
				<button type="button" class="btn btn-primary rounded-pill" data-confirm-submit>Confirm</button>
			</div>
		</div>
	`;
	document.body.appendChild(modal);

	return {
		modal,
		title: modal.querySelector('#action-confirmation-title'),
		message: modal.querySelector('#action-confirmation-message'),
		icon: modal.querySelector('[data-confirm-icon]'),
		confirmButton: modal.querySelector('[data-confirm-submit]'),
		cancelButtons: modal.querySelectorAll('[data-confirm-cancel]'),
	};
};

const initializeConfirmationForms = () => {
	const forms = Array.from(document.querySelectorAll('form[data-confirm-action]'));

	if (!forms.length) {
		return;
	}

	const confirmation = createActionConfirmationModal();
	let pendingForm = null;
	let pendingSubmitter = null;
	let lastActiveElement = null;

	const closeConfirmation = () => {
		confirmation.modal.classList.remove('is-open', 'is-danger');
		confirmation.modal.setAttribute('aria-hidden', 'true');
		document.body.classList.remove('action-confirmation-open');
		pendingForm = null;
		pendingSubmitter = null;

		if (lastActiveElement instanceof HTMLElement) {
			lastActiveElement.focus();
		}

		lastActiveElement = null;
	};

	const openConfirmation = (form, submitter) => {
		const variant = form.getAttribute('data-confirm-variant') ?? 'primary';
		const isDanger = variant === 'danger';
		const title = form.getAttribute('data-confirm-title') ?? (isDanger ? 'Confirm important action' : 'Confirm action');
		const message = form.getAttribute('data-confirm-message') ?? 'Continue with this action?';
		const label = form.getAttribute('data-confirm-label') ?? (isDanger ? 'Continue' : 'Confirm');

		pendingForm = form;
		pendingSubmitter = submitter;
		lastActiveElement = document.activeElement;

		confirmation.title.textContent = title;
		confirmation.message.textContent = message;
		confirmation.confirmButton.textContent = label;
		confirmation.confirmButton.className = `btn ${isDanger ? 'btn-danger' : 'btn-primary'} rounded-pill`;

		if (confirmation.icon instanceof HTMLElement) {
			confirmation.icon.innerHTML = `<i class="bi ${isDanger ? 'bi-exclamation-triangle' : 'bi-check2-circle'}"></i>`;
		}

		confirmation.modal.classList.toggle('is-danger', isDanger);
		confirmation.modal.classList.add('is-open');
		confirmation.modal.setAttribute('aria-hidden', 'false');
		document.body.classList.add('action-confirmation-open');

		const focusTarget = isDanger ? confirmation.modal.querySelector('[data-confirm-cancel]') : confirmation.confirmButton;
		if (focusTarget instanceof HTMLElement) {
			focusTarget.focus();
		}
	};

	forms.forEach((form) => {
		let lastSubmitter = null;

		form.querySelectorAll('button[type="submit"], input[type="submit"]').forEach((submitter) => {
			submitter.addEventListener('click', () => {
				lastSubmitter = submitter;
			});
		});

		form.addEventListener('submit', (event) => {
			if (form.dataset.confirmed === 'true') {
				delete form.dataset.confirmed;
				return;
			}

			event.preventDefault();
			openConfirmation(form, event.submitter ?? lastSubmitter);
		});
	});

	confirmation.cancelButtons.forEach((button) => {
		button.addEventListener('click', closeConfirmation);
	});

	confirmation.confirmButton.addEventListener('click', () => {
		if (!(pendingForm instanceof HTMLFormElement)) {
			closeConfirmation();
			return;
		}

		const form = pendingForm;
		const submitter = pendingSubmitter;
		closeConfirmation();
		form.dataset.confirmed = 'true';

		if (typeof form.requestSubmit === 'function') {
			form.requestSubmit(submitter instanceof HTMLElement ? submitter : undefined);
			return;
		}

		form.submit();
	});

	document.addEventListener('keydown', (event) => {
		if (!confirmation.modal.classList.contains('is-open') || event.key !== 'Escape') {
			return;
		}

		closeConfirmation();
	});
};

const initializeInlineFieldWarnings = () => {
	const fields = Array.from(document.querySelectorAll('[data-live-validate]'));

	if (!fields.length) {
		return;
	}

	const showWarning = (field, message) => {
		const warningId = field.getAttribute('data-warning-target');
		const warning = warningId ? document.getElementById(warningId) : null;

		if (!(warning instanceof HTMLElement)) {
			return;
		}

		warning.textContent = message;
		warning.style.display = message ? 'block' : 'none';
		field.classList.toggle('is-invalid', Boolean(message));
	};

	const clearWarning = (field) => {
		showWarning(field, '');
	};

	const isControlKey = (event) =>
		event.ctrlKey ||
		event.metaKey ||
		['Backspace', 'Delete', 'Tab', 'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown', 'Home', 'End', 'Enter'].includes(event.key);

	const sanitizeValue = (field, mode) => {
		const originalValue = field.value;
		let sanitizedValue = originalValue;

		if (mode === 'name-only') {
			sanitizedValue = originalValue.replace(/[^\p{L}\p{M}\s'.-]/gu, '');
		}

		if (mode === 'digits-only' || mode === 'nonnegative-integer') {
			sanitizedValue = originalValue.replace(/[^0-9]/g, '');
		}

		if (mode === 'nonnegative-number') {
			let hasDecimalPoint = false;
			sanitizedValue = Array.from(originalValue)
				.filter((character) => {
					if (/[0-9]/.test(character)) {
						return true;
					}

					if (character === '.' && !hasDecimalPoint) {
						hasDecimalPoint = true;
						return true;
					}

					return false;
				})
				.join('');
		}

		if (sanitizedValue !== originalValue) {
			field.value = sanitizedValue;
			return true;
		}

		return false;
	};

	fields.forEach((field) => {
		if (!(field instanceof HTMLInputElement)) {
			return;
		}

		const mode = field.getAttribute('data-live-validate');
		const invalidMessage = field.getAttribute('data-warning-message-invalid') ?? 'Invalid value.';
		const negativeMessage = field.getAttribute('data-warning-message-negative') ?? invalidMessage;

		const validate = (wasSanitized = false) => {
			const value = field.value.trim();

			if (wasSanitized) {
				showWarning(field, invalidMessage);
				return;
			}

			if (!value) {
				clearWarning(field);
				return;
			}

			if (mode === 'name-only' && /[^\p{L}\p{M}\s'.-]/u.test(value)) {
				showWarning(field, invalidMessage);
				return;
			}

			if (mode === 'nonnegative-number' || mode === 'nonnegative-integer') {
				const normalizedValue = value.replace(/,/g, '');
				const isNegative = normalizedValue.startsWith('-') || Number(normalizedValue) < 0;
				const isInvalidNumber = normalizedValue === '-' || Number.isNaN(Number(normalizedValue));
				const requiresNumberFormat = mode === 'nonnegative-number' && !/^\d+(\.\d+)?$/.test(normalizedValue);
				const requiresWholeNumber = mode === 'nonnegative-integer' && !/^\d+$/.test(normalizedValue);

				if (isNegative) {
					showWarning(field, negativeMessage);
					return;
				}

				if (isInvalidNumber || requiresNumberFormat || requiresWholeNumber) {
					showWarning(field, invalidMessage);
					return;
				}
			}

			if (mode === 'digits-only' && /[^0-9]/.test(value)) {
				showWarning(field, invalidMessage);
				return;
			}

			clearWarning(field);
		};

		field.addEventListener('input', () => {
			const wasSanitized = sanitizeValue(field, mode);
			validate(wasSanitized);
		});
		field.addEventListener('blur', () => validate());

		if (mode === 'name-only' || mode === 'digits-only' || mode === 'nonnegative-number' || mode === 'nonnegative-integer') {
			field.addEventListener('keydown', (event) => {
				if (isControlKey(event)) {
					return;
				}

				if (event.key === '-' || event.key === 'Subtract') {
					event.preventDefault();
					showWarning(field, mode === 'name-only' ? invalidMessage : negativeMessage);
					return;
				}

				if (event.key.length !== 1) {
					return;
				}

				if (mode === 'name-only' && !/[\p{L}\p{M}\s'.-]/u.test(event.key)) {
					event.preventDefault();
					showWarning(field, invalidMessage);
					return;
				}

				if ((mode === 'digits-only' || mode === 'nonnegative-integer') && !/[0-9]/.test(event.key)) {
					event.preventDefault();
					showWarning(field, invalidMessage);
					return;
				}

				if (mode === 'nonnegative-number' && !/[0-9.]/.test(event.key)) {
					event.preventDefault();
					showWarning(field, invalidMessage);
					return;
				}

				if (mode === 'nonnegative-number' && event.key === '.' && field.value.includes('.')) {
					event.preventDefault();
					showWarning(field, invalidMessage);
				}
			});
		}

		validate();
	});
};

const initializeLegalModals = () => {
	const shell = document.querySelector('.auth-legal-modal-shell');
	const triggers = Array.from(document.querySelectorAll('[data-legal-modal-trigger]'));
	const modals = Array.from(document.querySelectorAll('[data-legal-modal]'));

	if (!(shell instanceof HTMLElement) || !triggers.length || !modals.length) {
		return;
	}

	let activeModal = null;
	let lastTrigger = null;

	const closeModal = () => {
		if (!(activeModal instanceof HTMLElement)) {
			return;
		}

		activeModal.classList.remove('is-open');
		activeModal.setAttribute('aria-hidden', 'true');
		shell.classList.remove('is-open');
		shell.setAttribute('aria-hidden', 'true');
		document.body.classList.remove('modal-open');

		const triggerToFocus = lastTrigger;
		activeModal = null;
		lastTrigger = null;

		if (triggerToFocus instanceof HTMLElement) {
			triggerToFocus.focus();
		}
	};

	const openModal = (modalId, trigger) => {
		const modal = document.getElementById(modalId);

		if (!(modal instanceof HTMLElement)) {
			return;
		}

		modals.forEach((item) => {
			item.classList.remove('is-open');
			item.setAttribute('aria-hidden', 'true');
		});

		activeModal = modal;
		lastTrigger = trigger;

		shell.classList.add('is-open');
		shell.setAttribute('aria-hidden', 'false');
		modal.classList.add('is-open');
		modal.setAttribute('aria-hidden', 'false');
		document.body.classList.add('modal-open');
		modal.focus();
	};

	triggers.forEach((trigger) => {
		trigger.addEventListener('click', (event) => {
			event.preventDefault();
			event.stopPropagation();
			openModal(trigger.getAttribute('data-legal-modal-trigger'), trigger);
		});
	});

	shell.querySelectorAll('[data-legal-modal-close]').forEach((element) => {
		element.addEventListener('click', closeModal);
	});

	document.addEventListener('keydown', (event) => {
		if (!shell.classList.contains('is-open')) {
			return;
		}

		if (event.key === 'Escape') {
			closeModal();
		}
	});
};

const initializeReserveIntent = () => {
	const url = new URL(window.location.href);
	if (!url.searchParams.has('reserve')) {
		return;
	}

	const reserveCard = document.getElementById('reserve-card');
	if (!(reserveCard instanceof HTMLElement)) {
		return;
	}

	reserveCard.scrollIntoView({ behavior: 'smooth', block: 'start' });

	const quantityInput = reserveCard.querySelector('input[name="quantity"]');
	if (quantityInput instanceof HTMLElement) {
		quantityInput.focus({ preventScroll: true });
	}
};

const initializePasswordToggles = () => {
	const toggles = Array.from(document.querySelectorAll('[data-password-toggle]'));
	if (!toggles.length) {
		return;
	}

	toggles.forEach((toggle) => {
		if (!(toggle instanceof HTMLButtonElement)) {
			return;
		}

		const targetId = toggle.getAttribute('data-password-target') ?? '';
		const field = targetId ? document.getElementById(targetId) : null;

		if (!(field instanceof HTMLInputElement)) {
			return;
		}

		const icon = toggle.querySelector('i');

		const sync = () => {
			const isHidden = field.type === 'password';
			toggle.setAttribute('aria-label', isHidden ? 'Show password' : 'Hide password');

			if (icon instanceof HTMLElement) {
				icon.className = `bi ${isHidden ? 'bi-eye' : 'bi-eye-slash'}`;
			}
		};

		toggle.addEventListener('click', () => {
			field.type = field.type === 'password' ? 'text' : 'password';
			sync();
		});

		sync();
	});
};

onReady(() => {
	initializeNavbarScrollState();
	initializeRevealAnimations();
	initializeImagePreview();
	initializeImageLightbox();
	initializeCountdowns();
	initializeLegalModals();
	initializeReserveIntent();
	initializePasswordToggles();
	initializeFilterForms();
	initializeDatePickers();
	initializeOptionDropdowns();
	initializeConfirmationForms();
	initializeInlineFieldWarnings();
});
