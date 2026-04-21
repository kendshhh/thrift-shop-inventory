import './bootstrap';

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
			return 'available now';
		}

		const totalSeconds = Math.floor(diffMs / 1000);
		const days = Math.floor(totalSeconds / 86400);
		const hours = Math.floor((totalSeconds % 86400) / 3600);
		const minutes = Math.floor((totalSeconds % 3600) / 60);

		if (days > 0) {
			return `${days}d ${hours}h`;
		}

		if (hours > 0) {
			return `${hours}h ${minutes}m`;
		}

		return `${Math.max(minutes, 0)}m`;
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

			label.textContent = formatCountdown(target);
		});
	};

	syncCountdowns();
	window.setInterval(syncCountdowns, 30000);
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

onReady(() => {
	initializeNavbarScrollState();
	initializeRevealAnimations();
	initializeImagePreview();
	initializeImageLightbox();
	initializeCountdowns();
	initializeLegalModals();
});
