const menuToggle = document.getElementById('menu-toggle');
const sidebar = document.getElementById('sidebar');
const backdrop = document.getElementById('backdrop');

menuToggle.addEventListener('click', () => {
    sidebar.classList.toggle('active');
    backdrop.style.display = sidebar.classList.contains('active') ? 'block' : 'none';
});

backdrop.addEventListener('click', () => {
    sidebar.classList.remove('active');
    backdrop.style.display = 'none';
});

function submitProfileForm(event) {
event.preventDefault();

const form = document.getElementById('profile-form');
const formData = new FormData(form);

// Add X-Requested-With header to identify AJAX request
fetch(window.location.href, {
method: 'POST',
body: formData,
headers: {
    'X-Requested-With': 'XMLHttpRequest'
}
})
.then(response => {
if (!response.ok) {
    throw new Error('Network response was not ok');
}
return response.json();
})
.then(data => {
if (data.success) {
    Swal.fire({
        icon: 'success',
        title: 'Success!',
        text: data.message
    }).then(() => {
        // Reload the page to show updated profile
        window.location.reload();
    });
} else {
    Swal.fire({
        icon: 'error',
        title: 'Error',
        text: data.message || 'An error occurred while updating your profile.'
    });
}
})
.catch(error => {
console.error('Error:', error);
Swal.fire({
    icon: 'error',
    title: 'Error',
    text: 'An unexpected error occurred while updating your profile.'
});
});
}
document.getElementById('logoutButton').addEventListener('click', function(event) {
  event.preventDefault(); // Prevent the default link behavior

  // Display a confirmation dialog using SweetAlert
  Swal.fire({
      title: 'Are you sure?',
      text: "You will be logged out!",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#3085d6',
      cancelButtonColor: '#d33',
      confirmButtonText: 'Yes, log me out!'
  }).then((result) => {
      if (result.isConfirmed) {
          // Create a form to send POST request to logout.php
          const form = document.createElement('form');
          form.method = 'POST';
          form.action = 'logout.php';

          // Create a hidden input to signal logout confirmation
          const input = document.createElement('input');
          input.type = 'hidden';
          input.name = 'confirm_logout';
          input.value = 'yes';

          form.appendChild(input);
          document.body.appendChild(form);

          // Submit the form, logging out the user
          form.submit();
      }
  });
});