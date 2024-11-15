// notification_scripts.js
document.addEventListener('DOMContentLoaded', function() {
  loadNotifications();
});

function loadNotifications() {
  fetch('notifications_history.php')
      .then(response => response.json())
      .then(notifications => {
          displayNotifications(notifications);
      })
      .catch(error => {
          console.error('Error:', error);
          document.getElementById('notifications-container').innerHTML = `
              <div class="alert alert-danger">
                  Failed to load notifications. Please try again later.
              </div>
          `;
      });
}

function displayNotifications(notifications) {
  const container = document.getElementById('notifications-container');
  
  if (notifications.length === 0) {
      container.innerHTML = `
          <div class="alert alert-info">
              You haven't received any notifications yet.
          </div>
      `;
      return;
  }

  const notificationsHTML = notifications.map(notification => `
      <div class="card notification-card ${notification.status === 'success' ? 'success' : 'failed'}">
          <div class="card-body">
              <h5 class="card-title">${escapeHtml(notification.project_title)}</h5>
              <h6 class="card-subtitle mb-2 text-muted">
                  Category: ${escapeHtml(notification.category_name)}
              </h6>
              <p class="card-text">
                  <small class="text-muted">
                      Sent: ${new Date(notification.sent_at).toLocaleString()}
                  </small>
              </p>
              ${notification.status === 'failed' ? `
                  <p class="text-danger">
                      <small>Error: ${escapeHtml(notification.error_message)}</small>
                  </p>
              ` : ''}
              <span class="badge ${notification.status === 'success' ? 'bg-success' : 'bg-danger'}">
                  ${notification.status}
              </span>
          </div>
      </div>
  `).join('');

  container.innerHTML = notificationsHTML;
}

function escapeHtml(unsafe) {
  return unsafe
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#039;");
}