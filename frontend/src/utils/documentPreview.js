/**
 * Opens a document for preview in a new browser tab
 * @param {string} url - The document URL
 * @param {string} title - The document title (optional)
 */
export const openDocumentPreview = (url, title = 'Document Preview') => {
  // Open PDF directly in new tab - browser will handle PDF viewing
  const newWindow = window.open(url, '_blank');
  if (!newWindow) {
    // Fallback if popup is blocked
    alert('Popup blocat. Vă rugăm să permiteți popup-urile pentru această pagină și încercați din nou.');
  }
};

/**
 * Downloads a document file
 * @param {string} url - The document URL
 * @param {string} filename - The filename for download
 */
export const downloadDocument = async (url, filename) => {
  try {
    // Create a temporary link element for download
    const link = document.createElement('a');
    link.href = url;
    link.download = filename || 'document.pdf';
    link.target = '_blank';
    
    // Append to body, click, and remove
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  } catch (error) {
    console.error('Error downloading file:', error);
    alert('Eroare la descărcarea fișierului. Încercați din nou.');
  }
};

/**
 * Gets the correct document URL based on filename
 * @param {string} filename - The document filename
 * @returns {string} - The full document URL
 */
export const getDocumentUrl = (filename) => {
  // Use relative path from public directory
  return `/documents/${filename}`;
};

/**
 * Legacy function for backward compatibility - now opens in new tab
 * @param {string} url - The document URL
 * @param {string} title - The document title
 * @param {string} filename - The filename (optional)
 * @returns {object} - Preview modal state object (for compatibility)
 */
export const createPreviewModalState = (url, title, filename = '') => {
  // Instead of returning modal state, directly open the document
  openDocumentPreview(url, title);
  
  // Return empty state since we're not using modal anymore
  return {
    isOpen: false,
    url: '',
    title: '',
    downloadUrl: url
  };
};