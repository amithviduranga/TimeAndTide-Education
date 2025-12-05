/*!
 * Font Awesome Fallback JavaScript
 * Detects when Font Awesome fails to load and provides alternative solutions
 * Version: 1.0.0
 * Date: 2024-11-25
 */

(function() {
    'use strict';

    // Configuration
    const config = {
        // Time to wait for Font Awesome to load (in milliseconds)
        loadTimeout: 3000,
        
        // Alternative CDN URLs to try
        fallbackUrls: [
            'https://use.fontawesome.com/releases/v6.7.1/css/all.css',
            'https://maxcdn.bootstrapcdn.com/font-awesome/6.7.1/css/all.min.css',
            'https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.7.1/css/all.min.css'
        ],
        
        // Icons used in the site with their Unicode values
        iconMap: {
            'fa-passport': '\uf5ab',
            'fa-university': '\uf19c',
            'fa-file-alt': '\uf15c',
            'fa-money-bill-wave': '\uf53a',
            'fa-plane': '\uf072',
            'fa-redo-alt': '\uf2f9',
            'fa-check-circle': '\uf058',
            'fa-eye': '\uf06e',
            'fa-user-friends': '\uf500',
            'fa-graduation-cap': '\uf19d',
            'fa-map-marker-alt': '\uf3c5',
            'fa-phone': '\uf095',
            'fa-envelope': '\uf0e0',
            'fa-clock': '\uf017',
            'fa-facebook': '\uf09a',
            'fa-twitter': '\uf099',
            'fa-linkedin': '\uf08c',
            'fa-instagram': '\uf16d'
        },
        
        // Emoji fallbacks for complete failure
        emojiFallbacks: {
            'fa-passport': '🛂',
            'fa-university': '🏛️',
            'fa-file-alt': '📄',
            'fa-money-bill-wave': '💰',
            'fa-plane': '✈️',
            'fa-redo-alt': '↻',
            'fa-check-circle': '✓',
            'fa-eye': '👁️',
            'fa-user-friends': '👥',
            'fa-graduation-cap': '🎓',
            'fa-map-marker-alt': '📍',
            'fa-phone': '📞',
            'fa-envelope': '📧',
            'fa-clock': '🕐'
        }
    };

    /**
     * Check if Font Awesome has loaded properly
     * @returns {boolean} True if Font Awesome is loaded
     */
    function isFontAwesomeLoaded() {
        // Create a test element with a Font Awesome icon
        const testElement = document.createElement('i');
        testElement.className = 'fas fa-check';
        testElement.style.position = 'absolute';
        testElement.style.left = '-9999px';
        testElement.style.fontSize = '16px';
        
        document.body.appendChild(testElement);
        
        // Get computed styles
        const computedStyle = window.getComputedStyle(testElement, ':before');
        const content = computedStyle.getPropertyValue('content');
        const fontFamily = computedStyle.getPropertyValue('font-family');
        
        document.body.removeChild(testElement);
        
        // Check if the icon loaded (content should be a Unicode character)
        return content && content !== 'none' && content !== '""' && 
               (fontFamily.indexOf('Font Awesome') !== -1 || 
                fontFamily.indexOf('FontAwesome') !== -1);
    }

    /**
     * Load alternative Font Awesome CDN
     * @param {string} url - CDN URL to load
     * @returns {Promise} Promise that resolves when CSS is loaded
     */
    function loadAlternativeCDN(url) {
        return new Promise((resolve, reject) => {
            const link = document.createElement('link');
            link.rel = 'stylesheet';
            link.href = url;
            link.crossOrigin = 'anonymous';
            
            link.onload = function() {
                console.log('Font Awesome fallback loaded successfully:', url);
                resolve(url);
            };
            
            link.onerror = function() {
                console.warn('Font Awesome fallback failed to load:', url);
                reject(new Error(`Failed to load: ${url}`));
            };
            
            // Add to head
            document.head.appendChild(link);
            
            // Timeout fallback
            setTimeout(() => {
                reject(new Error(`Timeout loading: ${url}`));
            }, 5000);
        });
    }

    /**
     * Apply CSS-based fallbacks using Unicode characters
     */
    function applyCSSFallbacks() {
        const style = document.createElement('style');
        style.id = 'font-awesome-css-fallback';
        
        let css = `
            /* Font Awesome CSS Fallbacks */
            .fas, .fab, .far { 
                font-family: Arial, sans-serif; 
                font-weight: 900; 
                font-style: normal; 
            }
        `;
        
        // Add Unicode content for each icon
        for (const [iconClass, unicode] of Object.entries(config.iconMap)) {
            css += `
                .${iconClass}::before, 
                .fas.${iconClass}::before,
                .fab.${iconClass}::before { 
                    content: "${unicode}"; 
                }
            `;
        }
        
        style.textContent = css;
        document.head.appendChild(style);
        console.log('Font Awesome CSS fallbacks applied');
    }

    /**
     * Apply emoji fallbacks as a last resort
     */
    function applyEmojiFallbacks() {
        const icons = document.querySelectorAll('i[class*="fa-"]');
        
        icons.forEach(icon => {
            const classes = icon.className.split(' ');
            const iconClass = classes.find(cls => cls.startsWith('fa-'));
            
            if (iconClass && config.emojiFallbacks[iconClass]) {
                // Replace icon with emoji
                icon.textContent = config.emojiFallbacks[iconClass];
                icon.className += ' icon-emoji-fallback';
                icon.style.fontFamily = 'inherit';
                icon.style.fontWeight = 'normal';
            }
        });
        
        console.log('Font Awesome emoji fallbacks applied');
    }

    /**
     * Try multiple CDN fallbacks in sequence
     */
    async function tryFallbackCDNs() {
        for (const url of config.fallbackUrls) {
            try {
                await loadAlternativeCDN(url);
                
                // Wait a bit and check if it worked
                await new Promise(resolve => setTimeout(resolve, 1000));
                
                if (isFontAwesomeLoaded()) {
                    console.log('Font Awesome successfully loaded from fallback CDN');
                    return true;
                }
            } catch (error) {
                console.warn('Fallback CDN failed:', error.message);
                continue;
            }
        }
        
        return false;
    }

    /**
     * Main initialization function
     */
    async function init() {
        // Wait for DOM to be ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', init);
            return;
        }

        console.log('Checking Font Awesome status...');
        
        // Wait for potential Font Awesome load
        await new Promise(resolve => setTimeout(resolve, config.loadTimeout));
        
        // Check if Font Awesome loaded successfully
        if (isFontAwesomeLoaded()) {
            console.log('Font Awesome loaded successfully');
            return;
        }
        
        console.warn('Font Awesome failed to load, attempting fallbacks...');
        
        // Try alternative CDNs first
        const cdnSuccess = await tryFallbackCDNs();
        
        if (!cdnSuccess) {
            console.warn('All CDN fallbacks failed, applying CSS fallbacks...');
            applyCSSFallbacks();
            
            // Wait a bit to see if CSS fallbacks work
            await new Promise(resolve => setTimeout(resolve, 1000));
            
            // If still no success, apply emoji fallbacks
            if (!isFontAwesomeLoaded()) {
                console.warn('CSS fallbacks insufficient, applying emoji fallbacks...');
                applyEmojiFallbacks();
            }
        }
    }

    /**
     * Utility function to manually trigger fallback check
     * Can be called from console if needed
     */
    window.checkFontAwesome = function() {
        if (isFontAwesomeLoaded()) {
            console.log('✅ Font Awesome is working properly');
        } else {
            console.log('❌ Font Awesome is not working');
            init();
        }
    };

    /**
     * Add a visual indicator for development
     */
    function addDebugIndicator() {
        if (window.location.hostname === 'localhost' || window.location.hostname.includes('127.0.0.1')) {
            const indicator = document.createElement('div');
            indicator.id = 'fa-debug-indicator';
            indicator.style.cssText = `
                position: fixed; 
                top: 10px; 
                right: 10px; 
                background: rgba(0,0,0,0.8); 
                color: white; 
                padding: 5px 10px; 
                border-radius: 3px; 
                font-size: 12px; 
                z-index: 9999;
                font-family: monospace;
            `;
            
            const updateIndicator = () => {
                const status = isFontAwesomeLoaded() ? '✅ FA OK' : '❌ FA FAIL';
                indicator.textContent = status;
            };
            
            document.body.appendChild(indicator);
            updateIndicator();
            
            // Update every 2 seconds
            setInterval(updateIndicator, 2000);
        }
    }

    // Initialize when script loads
    init();
    
    // Add debug indicator for development
    addDebugIndicator();
    
})();