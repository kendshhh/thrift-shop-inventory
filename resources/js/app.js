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

const initializeConfirmationForms = () => {
	const forms = Array.from(document.querySelectorAll('form[data-confirm-action]'));

	if (!forms.length) {
		return;
	}

	forms.forEach((form) => {
		let lastSubmitter = null;

		form.querySelectorAll('button[type="submit"], input[type="submit"]').forEach((submitter) => {
			submitter.addEventListener('click', () => {
				lastSubmitter = submitter;
			});
		});

		form.addEventListener('submit', (event) => {
			if (form.dataset.confirmed === 'true') {
				form.dataset.confirmed = 'false';
				return;
			}

			event.preventDefault();

			const expectedPhrase = (form.getAttribute('data-confirm-phrase') ?? 'confirm').trim();
			const normalizedExpectedPhrase = expectedPhrase.toLowerCase();
			const message =
				form.getAttribute('data-confirm-message') ??
				`Type "${expectedPhrase}" to continue with this action.`;
			const enteredValue = window.prompt(message, '');

			if (enteredValue === null) {
				return;
			}

			if (enteredValue.trim().toLowerCase() !== normalizedExpectedPhrase) {
				window.alert(`Please type "${expectedPhrase}" exactly to continue.`);
				return;
			}

			let confirmationInput = form.querySelector('input[name="confirmation_text"]');

			if (!(confirmationInput instanceof HTMLInputElement)) {
				confirmationInput = document.createElement('input');
				confirmationInput.type = 'hidden';
				confirmationInput.name = 'confirmation_text';
				form.appendChild(confirmationInput);
			}

			confirmationInput.value = enteredValue.trim();
			form.dataset.confirmed = 'true';

			if (typeof form.requestSubmit === 'function') {
				form.requestSubmit(lastSubmitter ?? undefined);
				return;
			}

			form.submit();
		});
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

	fields.forEach((field) => {
		if (!(field instanceof HTMLInputElement)) {
			return;
		}

		const mode = field.getAttribute('data-live-validate');
		const invalidMessage = field.getAttribute('data-warning-message-invalid') ?? 'Invalid value.';
		const negativeMessage = field.getAttribute('data-warning-message-negative') ?? invalidMessage;

		const validate = () => {
			const value = field.value.trim();

			if (!value) {
				clearWarning(field);
				return;
			}

			if (mode === 'nonnegative-number' || mode === 'nonnegative-integer') {
				const normalizedValue = value.replace(/,/g, '');
				const isNegative = normalizedValue.startsWith('-') || Number(normalizedValue) < 0;
				const isInvalidNumber = normalizedValue === '-' || Number.isNaN(Number(normalizedValue));
				const requiresWholeNumber = mode === 'nonnegative-integer' && !/^\d+$/.test(normalizedValue);

				if (isNegative) {
					showWarning(field, negativeMessage);
					return;
				}

				if (isInvalidNumber || requiresWholeNumber) {
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

		field.addEventListener('input', validate);
		field.addEventListener('blur', validate);

		if (mode === 'nonnegative-number' || mode === 'nonnegative-integer') {
			field.addEventListener('keydown', (event) => {
				if (isControlKey(event)) {
					return;
				}

				if (event.key === '-' || event.key === 'Subtract') {
					event.preventDefault();
					showWarning(field, negativeMessage);
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
