/**
 * LazyLoader JavaScript Class
 * 
 * Utility for lazy loading JavaScript libraries and assets.
 * This helps improve initial page load performance by only loading
 * libraries when they are actually needed.
 */
class LazyLoader {
    constructor() {
        this.loadedLibraries = new Set();
        this.loadingPromises = new Map();
    }
    
    /**
     * Load a JavaScript library dynamically
     * 
     * @param {string} src The source URL of the library
     * @param {string} libraryName The name of the library (for caching)
     * @returns {Promise} Promise that resolves when the library is loaded
     */
    async loadLibrary(src, libraryName = null) {
        const name = libraryName || src;
        
        // Return immediately if already loaded
        if (this.loadedLibraries.has(name)) {
            return Promise.resolve();
        }
        
        // Return existing promise if currently loading
        if (this.loadingPromises.has(name)) {
            return this.loadingPromises.get(name);
        }
        
        // Create new loading promise
        const promise = new Promise((resolve, reject) => {
            const script = document.createElement('script');
            script.src = src;
            script.async = true;
            
            script.onload = () => {
                this.loadedLibraries.add(name);
                this.loadingPromises.delete(name);
                resolve();
            };
            
            script.onerror = () => {
                this.loadingPromises.delete(name);
                reject(new Error(`Failed to load library: ${src}`));
            };
            
            document.head.appendChild(script);
        });
        
        this.loadingPromises.set(name, promise);
        return promise;
    }
    
    /**
     * Load a CSS file dynamically
     * 
     * @param {string} href The URL of the CSS file
     * @param {string} libraryName The name of the library (for caching)
     * @returns {Promise} Promise that resolves when the CSS is loaded
     */
    async loadCSS(href, libraryName = null) {
        const name = libraryName || href;
        
        // Return immediately if already loaded
        if (this.loadedLibraries.has(name)) {
            return Promise.resolve();
        }
        
        // Return existing promise if currently loading
        if (this.loadingPromises.has(name)) {
            return this.loadingPromises.get(name);
        }
        
        // Create new loading promise
        const promise = new Promise((resolve, reject) => {
            const link = document.createElement('link');
            link.rel = 'stylesheet';
            link.href = href;
            
            link.onload = () => {
                this.loadedLibraries.add(name);
                this.loadingPromises.delete(name);
                resolve();
            };
            
            link.onerror = () => {
                this.loadingPromises.delete(name);
                reject(new Error(`Failed to load CSS: ${href}`));
            };
            
            document.head.appendChild(link);
        });
        
        this.loadingPromises.set(name, promise);
        return promise;
    }
    
    /**
     * Load PDF.js library
     * 
     * @returns {Promise} Promise that resolves when PDF.js is loaded
     */
    async loadPDFJS() {
        try {
            await this.loadLibrary('https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js', 'pdfjs');
            
            // Configure PDF.js worker
            if (typeof pdfjsLib !== 'undefined') {
                pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
            }
            
            return Promise.resolve();
        } catch (error) {
            console.error('Failed to load PDF.js:', error);
            throw error;
        }
    }
    
    /**
     * Load Tesseract.js library for OCR
     * 
     * @returns {Promise} Promise that resolves when Tesseract.js is loaded
     */
    async loadTesseractJS() {
        try {
            await this.loadLibrary('https://cdn.jsdelivr.net/npm/tesseract.js@4.1.1/dist/tesseract.min.js', 'tesseract');
            return Promise.resolve();
        } catch (error) {
            console.error('Failed to load Tesseract.js:', error);
            throw error;
        }
    }
    
    /**
     * Load Chart.js library
     * 
     * @returns {Promise} Promise that resolves when Chart.js is loaded
     */
    async loadChartJS() {
        try {
            await this.loadLibrary('https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.min.js', 'chartjs');
            return Promise.resolve();
        } catch (error) {
            console.error('Failed to load Chart.js:', error);
            throw error;
        }
    }
    
    /**
     * Load a library with fallback URLs
     * 
     * @param {Array} urls Array of URLs to try in order
     * @param {string} libraryName The name of the library
     * @returns {Promise} Promise that resolves when the library is loaded
     */
    async loadLibraryWithFallback(urls, libraryName) {
        for (let i = 0; i < urls.length; i++) {
            try {
                await this.loadLibrary(urls[i], libraryName);
                return Promise.resolve();
            } catch (error) {
                console.warn(`Failed to load ${libraryName} from ${urls[i]}, trying next URL...`);
                if (i === urls.length - 1) {
                    throw new Error(`Failed to load ${libraryName} from all provided URLs`);
                }
            }
        }
    }
    
    /**
     * Check if a library is loaded
     * 
     * @param {string} libraryName The name of the library
     * @returns {boolean} True if the library is loaded
     */
    isLoaded(libraryName) {
        return this.loadedLibraries.has(libraryName);
    }
    
    /**
     * Get the loading status of a library
     * 
     * @param {string} libraryName The name of the library
     * @returns {string} Loading status: 'loaded', 'loading', or 'not_loaded'
     */
    getLoadingStatus(libraryName) {
        if (this.loadedLibraries.has(libraryName)) {
            return 'loaded';
        } else if (this.loadingPromises.has(libraryName)) {
            return 'loading';
        } else {
            return 'not_loaded';
        }
    }
    
    /**
     * Load multiple libraries in parallel
     * 
     * @param {Array} libraries Array of library objects with src and name properties
     * @returns {Promise} Promise that resolves when all libraries are loaded
     */
    async loadLibraries(libraries) {
        const promises = libraries.map(lib => 
            this.loadLibrary(lib.src, lib.name)
        );
        
        try {
            await Promise.all(promises);
            return Promise.resolve();
        } catch (error) {
            console.error('Failed to load some libraries:', error);
            throw error;
        }
    }
    
    /**
     * Load libraries conditionally based on user interaction
     * 
     * @param {string} eventType The event type to listen for
     * @param {string} selector The CSS selector for the element
     * @param {Array} libraries Array of libraries to load
     * @param {number} delay Delay in milliseconds before loading (optional)
     */
    loadOnInteraction(eventType, selector, libraries, delay = 0) {
        const element = document.querySelector(selector);
        if (!element) {
            console.warn(`Element not found: ${selector}`);
            return;
        }
        
        const loadLibraries = async () => {
            if (delay > 0) {
                await new Promise(resolve => setTimeout(resolve, delay));
            }
            
            try {
                await this.loadLibraries(libraries);
                console.log('Libraries loaded on interaction:', libraries.map(lib => lib.name));
            } catch (error) {
                console.error('Failed to load libraries on interaction:', error);
            }
        };
        
        element.addEventListener(eventType, loadLibraries, { once: true });
    }
    
    /**
     * Load libraries when they come into viewport
     * 
     * @param {string} selector The CSS selector for the element
     * @param {Array} libraries Array of libraries to load
     * @param {Object} options Intersection Observer options
     */
    loadOnViewport(selector, libraries, options = {}) {
        const defaultOptions = {
            root: null,
            rootMargin: '50px',
            threshold: 0.1
        };
        
        const observerOptions = { ...defaultOptions, ...options };
        
        const observer = new IntersectionObserver(async (entries) => {
            for (const entry of entries) {
                if (entry.isIntersecting) {
                    try {
                        await this.loadLibraries(libraries);
                        console.log('Libraries loaded on viewport:', libraries.map(lib => lib.name));
                        observer.unobserve(entry.target);
                    } catch (error) {
                        console.error('Failed to load libraries on viewport:', error);
                    }
                }
            }
        }, observerOptions);
        
        const element = document.querySelector(selector);
        if (element) {
            observer.observe(element);
        } else {
            console.warn(`Element not found: ${selector}`);
        }
    }
}

// Initialize lazy loader when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    window.lazyLoader = new LazyLoader();
});

// Global function to load PDF.js (for backward compatibility)
async function loadPDFJS() {
    if (window.lazyLoader) {
        return await window.lazyLoader.loadPDFJS();
    }
    throw new Error('LazyLoader not initialized');
}

// Global function to load Tesseract.js (for backward compatibility)
async function loadTesseractJS() {
    if (window.lazyLoader) {
        return await window.lazyLoader.loadTesseractJS();
    }
    throw new Error('LazyLoader not initialized');
}
