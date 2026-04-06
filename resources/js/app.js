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

onReady(() => {
	initializeNavbarScrollState();
	initializeRevealAnimations();
	initializeImagePreview();
});
