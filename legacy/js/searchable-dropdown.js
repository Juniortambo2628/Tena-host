/**
 * Searchable Dropdown Component
 * Replaces heavy select elements with searchable, lazy-loaded dropdowns
 */

class SearchableDropdown {
    constructor(selectElement, options = {}) {
        this.originalSelect = selectElement;
        this.options = {
            placeholder: 'Search...',
            noResultsText: 'No results found',
            minSearchLength: 0,
            lazyLoad: true,
            searchDebounceTime: 200, // ms - wait time before searching
            enableRecentPopular: true, // Enable recent/popular countries feature
            popularCountries: ['United States', 'United Kingdom', 'Canada', 'Australia', 'India'], // Most common
            ...options
        };
        
        this.isOpen = false;
        this.filteredOptions = [];
        this.allOptions = [];
        this.selectedValue = '';
        this.selectedText = '';
        this.searchTimeout = null; // For debouncing
        this.recentSelections = this.loadRecentSelections(); // Load recent countries
        this.searchCache = new Map(); // Cache for search results
        this.maxCacheSize = 50; // Maximum cached searches
        
        // Virtual scrolling properties
        this.visibleItems = 10; // Number of items to render at once
        this.itemHeight = 40; // Height of each dropdown item in pixels
        this.scrollTop = 0; // Current scroll position
        this.enableVirtualScroll = true; // Enable virtual scrolling
        
        // Web Worker support (optional - falls back to main thread)
        this.useWebWorker = false; // Set to true to enable Web Worker
        this.searchWorker = null;
        if (this.useWebWorker && typeof Worker !== 'undefined') {
            try {
                this.searchWorker = new Worker('js/search-worker.js');
                this.setupWorkerListener();
            } catch (error) {
                console.warn('Web Worker not available, using main thread search');
                this.useWebWorker = false;
            }
        }
        
        this.init();
    }
    
    setupWorkerListener() {
        this.searchWorker.addEventListener('message', (e) => {
            const { success, results, searchTerm, error } = e.data;
            
            if (success) {
                this.filteredOptions = results;
                this.renderFilteredOptions();
            } else {
                console.error('Search worker error:', error);
                // Fallback to main thread
                this.useWebWorker = false;
                this.filterOptions(searchTerm);
            }
        });
    }
    
    init() {
        this.extractOptions();
        this.createDropdown();
        this.bindEvents();
        this.hideOriginalSelect();
    }
    
    extractOptions() {
        const options = this.originalSelect.querySelectorAll('option');
        this.allOptions = Array.from(options).map(option => ({
            value: option.value,
            text: option.textContent,
            searchText: option.textContent.toLowerCase()
        }));
        this.filteredOptions = [...this.allOptions];
    }
    
    createDropdown() {
        // Create dropdown container
        this.container = document.createElement('div');
        this.container.className = 'searchable-dropdown-container';
        this.container.innerHTML = `
            <div class="searchable-dropdown-input" tabindex="0">
                <span class="selected-text">${this.options.placeholder}</span>
                <i class="fas fa-chevron-down dropdown-arrow"></i>
            </div>
            <div class="searchable-dropdown-menu" style="display: none;">
                <div class="search-input-container">
                    <input type="text" class="search-input" placeholder="${this.options.placeholder}">
                    <i class="fas fa-search search-icon"></i>
                </div>
                <div class="dropdown-options">
                    ${this.renderOptions()}
                </div>
            </div>
        `;
        
        // Insert after original select
        this.originalSelect.parentNode.insertBefore(this.container, this.originalSelect.nextSibling);
        
        // Get references to elements
        this.input = this.container.querySelector('.searchable-dropdown-input');
        this.menu = this.container.querySelector('.searchable-dropdown-menu');
        this.searchInput = this.container.querySelector('.search-input');
        this.optionsContainer = this.container.querySelector('.dropdown-options');
        this.selectedTextElement = this.container.querySelector('.selected-text');
        this.arrow = this.container.querySelector('.dropdown-arrow');
    }
    
    renderOptions() {
        if (!this.enableVirtualScroll || this.filteredOptions.length <= this.visibleItems) {
            // Render all items if virtual scrolling disabled or few items
            return this.filteredOptions.map(option => this.renderSingleOption(option)).join('');
        }
        
        // Virtual scrolling: only render visible items
        const startIndex = Math.floor(this.scrollTop / this.itemHeight);
        const endIndex = Math.min(startIndex + this.visibleItems, this.filteredOptions.length);
        const visibleOptions = this.filteredOptions.slice(startIndex, endIndex);
        
        const totalHeight = this.filteredOptions.length * this.itemHeight;
        const offsetY = startIndex * this.itemHeight;
        
        return `
            <div class="virtual-scroll-spacer" style="height: ${totalHeight}px; position: relative;">
                <div class="virtual-scroll-content" style="transform: translateY(${offsetY}px); position: absolute; width: 100%;">
                    ${visibleOptions.map(option => this.renderSingleOption(option)).join('')}
                </div>
            </div>
        `;
    }
    
    renderSingleOption(option) {
        const isRecent = this.recentSelections.includes(option.text);
        const isPopular = this.options.popularCountries.includes(option.text);
        const classes = ['dropdown-option'];
        if (isRecent) classes.push('recent');
        else if (isPopular) classes.push('popular');
        
        return `
            <div class="${classes.join(' ')}" data-value="${option.value}" style="height: ${this.itemHeight}px; display: flex; align-items: center;">
                ${option.text}
                ${isRecent ? '<i class="fas fa-clock ms-2" style="font-size: 10px; opacity: 0.7;"></i>' : ''}
                ${isPopular && !isRecent ? '<i class="fas fa-star ms-2" style="font-size: 10px; opacity: 0.7;"></i>' : ''}
            </div>
        `;
    }
    
    bindEvents() {
        // Toggle dropdown
        this.input.addEventListener('click', (e) => {
            e.stopPropagation();
            this.toggle();
        });
        
        // Search functionality with debouncing
        this.searchInput.addEventListener('input', (e) => {
            clearTimeout(this.searchTimeout);
            this.searchTimeout = setTimeout(() => {
                this.filterOptions(e.target.value);
            }, this.options.searchDebounceTime);
        });
        
        // Option selection
        this.optionsContainer.addEventListener('click', (e) => {
            const option = e.target.closest('.dropdown-option');
            if (option) {
                this.selectOption(option.dataset.value);
            }
        });
        
        // Close on outside click
        document.addEventListener('click', (e) => {
            if (!this.container.contains(e.target)) {
                this.close();
            }
        });
        
        // Keyboard navigation
        this.input.addEventListener('keydown', (e) => {
            this.handleKeydown(e);
        });
        
        this.searchInput.addEventListener('keydown', (e) => {
            this.handleKeydown(e);
        });
        
        // Virtual scroll event
        this.optionsContainer.addEventListener('scroll', (e) => {
            if (this.enableVirtualScroll) {
                this.scrollTop = e.target.scrollTop;
                this.renderFilteredOptions();
            }
        });
    }
    
    filterOptions(searchTerm) {
        const term = searchTerm.toLowerCase().trim();
        
        // Use Web Worker if enabled and available
        if (this.useWebWorker && this.searchWorker && term) {
            this.searchWorker.postMessage({
                countries: this.allOptions,
                searchTerm: term,
                enableFuzzy: true
            });
            return; // Worker will handle the rest
        }
        
        if (!term && this.options.enableRecentPopular) {
            // Show recent + popular when no search term
            const recent = this.allOptions.filter(opt => 
                this.recentSelections.includes(opt.text)
            );
            const popular = this.allOptions.filter(opt => 
                this.options.popularCountries.includes(opt.text) && 
                !this.recentSelections.includes(opt.text)
            );
            const rest = this.allOptions.filter(opt => 
                !this.recentSelections.includes(opt.text) &&
                !this.options.popularCountries.includes(opt.text)
            );
            
            this.filteredOptions = [...recent, ...popular, ...rest];
        } else if (term) {
            // Check cache first
            if (this.searchCache.has(term)) {
                this.filteredOptions = this.searchCache.get(term);
                this.renderFilteredOptions();
                return;
            }
            
            // Perform fuzzy search with scoring
            this.filteredOptions = this.allOptions
                .map(option => ({
                    ...option,
                    score: this.fuzzyScore(option.searchText, term)
                }))
                .filter(option => option.score > 0)
                .sort((a, b) => b.score - a.score);
            
            // Cache results (LRU - Least Recently Used)
            if (this.searchCache.size >= this.maxCacheSize) {
                const firstKey = this.searchCache.keys().next().value;
                this.searchCache.delete(firstKey);
            }
            this.searchCache.set(term, [...this.filteredOptions]);
        } else {
            // Empty search - show all
            this.filteredOptions = [...this.allOptions];
        }
        
        this.renderFilteredOptions();
    }
    
    renderFilteredOptions() {
        this.optionsContainer.innerHTML = this.renderOptions();
        
        // Show no results message if no options
        if (this.filteredOptions.length === 0) {
            this.optionsContainer.innerHTML = `
                <div class="no-results">${this.options.noResultsText}</div>
            `;
        }
        
        // Setup Intersection Observer for flag lazy loading
        if (this.enableVirtualScroll && this.filteredOptions.length > this.visibleItems) {
            this.observeOptions();
        }
    }
    
    observeOptions() {
        // Lazy load flags for options that come into view
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const option = entry.target;
                    // Add loaded class for smooth transition
                    option.classList.add('loaded');
                    observer.unobserve(option);
                }
            });
        }, {
            root: this.optionsContainer,
            rootMargin: '50px', // Load flags 50px before they come into view
            threshold: 0.1
        });
        
        // Observe all dropdown options
        this.optionsContainer.querySelectorAll('.dropdown-option').forEach(option => {
            observer.observe(option);
        });
    }
    
    selectOption(value) {
        const option = this.allOptions.find(opt => opt.value === value);
        if (option) {
            this.selectedValue = value;
            this.selectedText = option.text;
            this.selectedTextElement.textContent = option.text;
            this.originalSelect.value = value;
            
            // Save to recent selections
            if (this.options.enableRecentPopular) {
                this.saveRecentSelection(option.text);
            }
            
            // Trigger change event on original select
            this.originalSelect.dispatchEvent(new Event('change', { bubbles: true }));
            
            this.close();
        }
    }
    
    saveRecentSelection(value) {
        let recent = this.loadRecentSelections();
        // Add to front, remove duplicates, limit to 5
        recent = [value, ...recent.filter(v => v !== value)].slice(0, 5);
        localStorage.setItem('tena_recent_countries', JSON.stringify(recent));
        this.recentSelections = recent;
    }
    
    loadRecentSelections() {
        try {
            return JSON.parse(localStorage.getItem('tena_recent_countries') || '[]');
        } catch {
            return [];
        }
    }
    
    fuzzyScore(text, query) {
        let score = 0;
        let queryIndex = 0;
        
        // Character matching with position bonus
        for (let i = 0; i < text.length && queryIndex < query.length; i++) {
            if (text[i] === query[queryIndex]) {
                // Higher score for earlier matches
                score += (query.length - queryIndex) * 10;
                queryIndex++;
            }
        }
        
        // Must match all query characters
        if (queryIndex !== query.length) return 0;
        
        // Bonus scoring
        if (text.startsWith(query)) {
            score += 100; // Starts with query
        } else if (text.includes(' ' + query)) {
            score += 50; // Word boundary match
        }
        
        // Exact match bonus
        if (text === query) {
            score += 200;
        }
        
        // Length penalty (prefer shorter matches)
        score -= text.length - query.length;
        
        return score;
    }
    
    toggle() {
        if (this.isOpen) {
            this.close();
        } else {
            this.open();
        }
    }
    
    open() {
        this.isOpen = true;
        this.menu.style.display = 'block';
        this.arrow.classList.add('rotated');
        this.input.classList.add('active');
        
        // Ensure dropdown stays within modal bounds
        this.adjustDropdownPosition();
        
        // Focus search input
        setTimeout(() => {
            this.searchInput.focus();
        }, 100);
    }
    
    adjustDropdownPosition() {
        const container = this.container;
        const menu = this.menu;
        const modal = container.closest('.modal');
        
        if (modal) {
            const modalRect = modal.getBoundingClientRect();
            const containerRect = container.getBoundingClientRect();
            const menuRect = menu.getBoundingClientRect();
            
            // Check if dropdown would extend beyond modal bottom
            const spaceBelow = modalRect.bottom - containerRect.bottom;
            const menuHeight = 180; // max-height from CSS
            
            if (spaceBelow < menuHeight) {
                // Position dropdown above the input
                menu.style.top = 'auto';
                menu.style.bottom = '100%';
                menu.style.marginTop = '0';
                menu.style.marginBottom = '4px';
            } else {
                // Reset to normal positioning
                menu.style.top = '100%';
                menu.style.bottom = 'auto';
                menu.style.marginTop = '4px';
                menu.style.marginBottom = '0';
            }
        }
    }
    
    close() {
        this.isOpen = false;
        this.menu.style.display = 'none';
        this.arrow.classList.remove('rotated');
        this.input.classList.remove('active');
        this.searchInput.value = '';
        this.filterOptions('');
    }
    
    handleKeydown(e) {
        const options = this.container.querySelectorAll('.dropdown-option');
        const currentIndex = Array.from(options).findIndex(option => 
            option.classList.contains('highlighted')
        );
        
        switch (e.key) {
            case 'ArrowDown':
                e.preventDefault();
                this.highlightOption(options[currentIndex + 1] || options[0]);
                break;
            case 'ArrowUp':
                e.preventDefault();
                this.highlightOption(options[currentIndex - 1] || options[options.length - 1]);
                break;
            case 'Enter':
                e.preventDefault();
                if (currentIndex >= 0) {
                    this.selectOption(options[currentIndex].dataset.value);
                }
                break;
            case 'Escape':
                this.close();
                break;
        }
    }
    
    highlightOption(option) {
        // Remove previous highlight (optimized - single query)
        const currentHighlight = this.container.querySelector('.dropdown-option.highlighted');
        if (currentHighlight) {
            currentHighlight.classList.remove('highlighted');
        }
        
        // Add highlight to current option
        if (option) {
            option.classList.add('highlighted');
            // Smooth scroll into view
            option.scrollIntoView({ 
                block: 'nearest',
                behavior: 'smooth'
            });
        }
    }
    
    hideOriginalSelect() {
        this.originalSelect.style.display = 'none';
    }
    
    getValue() {
        return this.selectedValue;
    }
    
    setValue(value) {
        const option = this.allOptions.find(opt => opt.value === value);
        if (option) {
            this.selectOption(value);
        }
    }
}

// Auto-initialize searchable dropdowns
document.addEventListener('DOMContentLoaded', function() {
    // Initialize country code dropdown
    const countryCodeSelect = document.getElementById('countryCodeSelect');
    if (countryCodeSelect) {
        new SearchableDropdown(countryCodeSelect, {
            placeholder: '(+...)',
            noResultsText: 'No country codes found'
        });
    }
    
    // Initialize country dropdown
    const countrySelect = document.querySelector('select[name="country"]');
    if (countrySelect) {
        new SearchableDropdown(countrySelect, {
            placeholder: 'country...',
            noResultsText: 'No countries found'
        });
    }
});

// Export for manual initialization
window.SearchableDropdown = SearchableDropdown;
