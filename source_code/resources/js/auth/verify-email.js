// Description: JavaScript for email verification page
import {attachLoadingSubmit} from '../utils/utils.js';

// Ensure jQuery is loaded
if (typeof $ === 'undefined') {
	throw new Error('This script requires jQuery');
}

// Constants and Variables
const resendFormId = 'resend';
const logoutFormId = 'logout';

// Attach loading state handlers to forms
[resendFormId, logoutFormId].forEach(attachLoadingSubmit);
