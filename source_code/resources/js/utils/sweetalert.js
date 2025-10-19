import 'sweetalert2/themes/bootstrap-5.css'; //<- Import SweetAlert2 Bootstrap 5 theme
import Swal from 'sweetalert2';

export const SwalModal = Swal.mixin({
	theme: 'bootstrap-5',
	showCloseButton: true,
	focusConfirm: false,
	customClass: {
		popup: 'swal-popup w-auto h-auto',
		title: 'd-flex justify-content-start align-items-center border-bottom pb-3 mb-3',
		closeButton: 'swal-close-btn fs-3',
		htmlContainer: 'w-auto h-auto p-1 overflow-x-hidden',
		confirmButton: 'btn btn-primary mx-1',
		cancelButton: 'btn btn-danger mx-1',
		icon: 'mb-4',
	}
});

export const SwalToast = Swal.mixin({
	toast: true,
	position: 'top-end',
	theme: 'bootstrap-5',
	showConfirmButton: false,
	timerProgressBar: true,
	timer: 5000,
	customClass: {
		popup: 'swal-popup bg-body-tertiary',
		timerProgressBar: 'swal-timer-progress-bar',
	}
});
