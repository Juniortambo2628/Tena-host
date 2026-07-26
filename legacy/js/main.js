(function ($) {
    "use strict";

    // Typing Animation
    var typingAnimation = function () {
        var text = "Tena... na Tena... na Tena";
        var typingElement = $('#typing-text');
        var index = 0;
        
        function typeChar() {
            if (index < text.length) {
                typingElement.text(typingElement.text() + text.charAt(index));
                index++;
                setTimeout(typeChar, 150); // Typing speed
            } else {
                // Reset and restart after a pause
                setTimeout(function() {
                    typingElement.text('');
                    index = 0;
                    typeChar();
                }, 2000);
            }
        }
        
        if (typingElement.length > 0) {
            typeChar();
        }
    };
    
    // Spinner
    var spinner = function () {
        // Start typing animation immediately
        typingAnimation();
        
        setTimeout(function () {
            if ($('#spinner').length > 0) {
                $('#spinner').removeClass('show');
            }
        }, 3000); // Show for 3 seconds to see the animation
    };
    spinner();
    
    
    // Initiate the wowjs
    new WOW().init();


    // Sticky Navbar
    $(window).scroll(function () {
        if ($(this).scrollTop() > 300) {
            $('.sticky-top').addClass('bg-white shadow-sm').css('top', '0px');
        } else {
            $('.sticky-top').removeClass('bg-white shadow-sm').css('top', '-150px');
        }
    });
    
    
    // Back to top button
    $(window).scroll(function () {
        if ($(this).scrollTop() > 100) {
            $('.back-to-top').fadeIn('slow');
        } else {
            $('.back-to-top').fadeOut('slow');
        }
    });
    $('.back-to-top').click(function () {
        $('html, body').animate({scrollTop: 0}, 1500, 'easeInOutExpo');
        return false;
    });

    // Smooth scroll for nav anchors and active link highlighting
    $('.navbar .nav-link').on('click', function (e) {
        var href = $(this).attr('href');
        if (href && href.charAt(0) === '#') {
            e.preventDefault();
            var target = $(href);
            if (target.length) {
                $('html, body').animate({scrollTop: target.offset().top - 70}, 800);
            }
        }
    });

    $(window).on('scroll', function () {
        var scrollPos = $(document).scrollTop();
        $('.navbar .nav-link').each(function () {
            var currLink = $(this);
            var refElement = $(currLink.attr("href"));
            if (refElement.length) {
                if (refElement.position().top - 80 <= scrollPos && refElement.position().top + refElement.height() > scrollPos) {
                    $('.navbar .nav-link').removeClass("active");
                    currLink.addClass("active");
                }
            }
        });
    });


    // Header carousel
    $(".header-carousel").owlCarousel({
        autoplay: true,
        smartSpeed: 1000,
        loop: true,
        dots: true,
        items: 1,
        animateIn: 'fadeIn',
        animateOut: 'fadeOut'
    });


    // Testimonials carousel
    $(".testimonial-carousel").owlCarousel({
        items: 1,
        autoplay: true,
        smartSpeed: 1000,
        animateIn: 'fadeIn',
        animateOut: 'fadeOut',
        dots: true,
        loop: true,
        nav: false
    });
    
    // Fullscreen image function with smooth transitions
    window.openFullscreen = function(img) {
        // Create fullscreen overlay
        var overlay = document.createElement('div');
        overlay.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0);
            z-index: 9999;
            display: flex;
            justify-content: center;
            align-items: center;
            cursor: pointer;
            opacity: 0;
            transition: all 0.3s ease;
        `;
        
        // Create fullscreen image
        var fullscreenImg = document.createElement('img');
        fullscreenImg.src = img.src;
        fullscreenImg.alt = img.alt;
        fullscreenImg.style.cssText = `
            max-width: 90%;
            max-height: 90%;
            object-fit: contain;
            cursor: pointer;
            transform: scale(0.8);
            transition: all 0.3s ease;
        `;
        
        // Add close functionality with smooth transition
        overlay.onclick = function() {
            overlay.style.opacity = '0';
            fullscreenImg.style.transform = 'scale(0.8)';
            setTimeout(function() {
                if (document.body.contains(overlay)) {
                    document.body.removeChild(overlay);
                }
            }, 300);
        };
        
        // Add to page
        overlay.appendChild(fullscreenImg);
        document.body.appendChild(overlay);
        
        // Trigger smooth opening animation
        setTimeout(function() {
            overlay.style.opacity = '1';
            overlay.style.background = 'rgba(0, 0, 0, 0.9)';
            fullscreenImg.style.transform = 'scale(1)';
        }, 10);
        
        // Close on Escape key
        var escapeHandler = function(e) {
            if (e.key === 'Escape') {
                overlay.click();
                document.removeEventListener('keydown', escapeHandler);
            }
        };
        document.addEventListener('keydown', escapeHandler);
    };
    
})(jQuery);

/* ROI Calculator with Live Updates (outside jQuery IIFE) */
document.addEventListener('DOMContentLoaded', function(){
    const mapping = [
        {range:'#numListingsRange', input:'#numListings'},
        {range:'#adrRange', input:'#adr'},
        {range:'#occupancyRange', input:'#occupancy'},
        {range:'#directRange', input:'#direct'},
        {range:'#pmFeeRange', input:'#pmFee'}
    ];
    
    // Set up bidirectional sync between sliders and inputs
    mapping.forEach(m => {
        const r = document.querySelector(m.range);
        const i = document.querySelector(m.input);
        if (!r || !i) return;
        
        r.addEventListener('input', ()=>{ 
            i.value = r.value; 
            calcROI(); // Live update on slider change
        });
        i.addEventListener('input', ()=>{ 
            r.value = i.value; 
            calcROI(); // Live update on input change
        });
        i.addEventListener('change', ()=>{ 
            r.value = i.value; 
            calcROI(); // Live update on input change
        });
    });

    // Add live update listeners to radio buttons
    const managerRadios = document.querySelectorAll('input[name="isManager"]');
    managerRadios.forEach(radio => {
        radio.addEventListener('change', calcROI);
    });

    function calcROI(){
        const listings = Number(document.getElementById('numListings').value) || 0;
        const adr = Number(document.getElementById('adr').value) || 0;
        const occupancy = (Number(document.getElementById('occupancy').value) || 0)/100;
        const direct = (Number(document.getElementById('direct').value) || 0)/100;
        const isManager = document.getElementById('managerYes') ? document.getElementById('managerYes').checked : false;
        const pmFee = (Number(document.getElementById('pmFee').value) || 0)/100;

        const nights = 30;
        const monthlyGross = listings * adr * nights * occupancy;
        const monthlyDirect = monthlyGross * direct;
        const otaFeeAvoided = monthlyDirect * 0.20;
        let managementCost = 0;
        if (isManager) managementCost = monthlyDirect * pmFee;
        const netBenefit = otaFeeAvoided - managementCost;
        const annual = netBenefit * 12;

        const fmt = v => '$' + Number(v).toLocaleString(undefined,{maximumFractionDigits:0});
        
        // Update values with animation
        const resultIds = ['monthlyGross', 'monthlyDirect', 'monthlySavings', 'netBenefit', 'annualBenefit'];
        const values = [monthlyGross, monthlyDirect, otaFeeAvoided, netBenefit, annual];
        
        resultIds.forEach((id, index) => {
            const element = document.getElementById(id);
            if (element) {
                // Add updating class
                element.classList.add('updating');
                
                // Update value
                element.textContent = fmt(values[index]);
                
                // Remove updating class and add updated animation
                setTimeout(() => {
                    element.classList.remove('updating');
                    element.classList.add('updated');
                    
                    // Remove updated class after animation
                    setTimeout(() => {
                        element.classList.remove('updated');
                    }, 600);
                }, 50);
            }
        });
    }

    // Only keep reset button functionality
    const resetBtn = document.getElementById('resetBtn');
    if (resetBtn) {
        resetBtn.addEventListener('click', ()=>{ 
            const form = document.getElementById('roiForm'); 
            if (form) {
                form.reset(); 
                calcROI(); // Update display after reset
            }
        });
    }
    
    // Initialize values on page load
    calcROI();
});

/* Country Code Select Display Handler */
document.addEventListener('DOMContentLoaded', function(){
    const countryCodeSelect = document.getElementById('countryCodeSelect');
    
    if (countryCodeSelect) {
        // Store original option texts for dropdown display
        const originalOptions = {};
        Array.from(countryCodeSelect.options).forEach(option => {
            if (option.value) {
                originalOptions[option.value] = option.textContent;
            }
        });
        
        // Handle selection change
        countryCodeSelect.addEventListener('change', function() {
            const selectedValue = this.value;
            if (selectedValue) {
                // Show only the country code in the selected field
                this.selectedOptions[0].textContent = selectedValue;
            }
        });
        
        // Handle focus - restore full text for dropdown
        countryCodeSelect.addEventListener('focus', function() {
            Array.from(this.options).forEach(option => {
                if (option.value && originalOptions[option.value]) {
                    option.textContent = originalOptions[option.value];
                }
            });
        });
        
        // Handle blur - show only code for selected option
        countryCodeSelect.addEventListener('blur', function() {
            const selectedValue = this.value;
            if (selectedValue) {
                this.selectedOptions[0].textContent = selectedValue;
            }
        });
    }
});

