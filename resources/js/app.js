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
			};
			reader.readAsDataURL(file);
		} else {
			previewDiv.style.display = 'none';
		}
	});
};

const initializeImageLightbox = () => {
	const lightbox = document.getElementById('image-lightbox');
	const lightboxImage = document.getElementById('image-lightbox-image');
	const lightboxCaption = document.getElementById('image-lightbox-title');
	const triggers = document.querySelectorAll('[data-lightbox-image]');

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
		lightboxImage.src = trigger.getAttribute('src') ?? '';
		lightboxImage.alt = trigger.getAttribute('alt') ?? 'Image preview';
		lightboxCaption.textContent = trigger.getAttribute('alt') ?? 'Image preview';
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

onReady(() => {
	initializeNavbarScrollState();
	initializeRevealAnimations();
	initializeImagePreview();
	initializeImageLightbox();
	initializeCountdowns();
});
