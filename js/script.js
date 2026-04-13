// Global variables
let currentUser = JSON.parse(localStorage.getItem('user')) || null;
let emergencyContacts = [];
let locationSharing = false;
let sosActive = false;

// API Helper
async function apiCall(url, method = 'GET', data = null) {
  try {
    const options = {
      method,
      headers: {
        'Content-Type': 'application/json'
      }
    };
    
    if (data && method !== 'GET') {
      options.body = JSON.stringify(data);
    }
    
    const response = await fetch(url, options);
    const result = await response.json();
    
    if (!response.ok) {
      throw new Error(result.message || 'Something went wrong');
    }
    
    return result;
  } catch (error) {
    console.error('API Error:', error);
    showNotification(`❌ ${error.message}`, 'danger');
    return null;
  }
}

// User Authentication
async function loginUser(event) {
  event.preventDefault();
  const email = document.getElementById('email').value;
  const password = document.getElementById('password').value;
  
  const result = await apiCall('../backend/auth/login.php', 'POST', { email, password });
  
  if (result && result.success) {
    currentUser = result.data.user;
    localStorage.setItem('user', JSON.stringify(currentUser));
    showNotification('✅ Login successful!', 'success');
    setTimeout(() => {
      window.location.href = 'dashboard.html';
    }, 1000);
  }
}

async function logoutUser() {
  localStorage.removeItem('user');
  currentUser = null;
  // Note: For a full logout, we'd also call a backend logout.php,
  // but for now clearing localStorage is a good start for the UI.
  await apiCall('../backend/auth/logout.php', 'POST'); 
  window.location.href = 'login.html';
}

async function registerUser(event) {
  event.preventDefault();
  const name = document.getElementById('name').value;
  const email = document.getElementById('email').value;
  const password = document.getElementById('password').value;
  const contact = document.getElementById('contact').value;
  
  const result = await apiCall('../backend/auth/register.php', 'POST', { name, email, password, contact });
  
  if (result && result.success) {
    currentUser = result.data.user;
    localStorage.setItem('user', JSON.stringify(currentUser));
    showNotification('✅ Registration successful!', 'success');
    setTimeout(() => {
      window.location.href = 'dashboard.html';
    }, 1500);
  }
}

// Emergency Functions
function sendSOS() {
  if (sosActive) {
    showNotification('⚠️ SOS already active!', 'warning');
    return;
  }
  
  const modalElement = document.getElementById('emergencyModal');
  if (modalElement) {
    const modal = new bootstrap.Modal(modalElement);
    modal.show();
  } else {
    // If modal not present (e.g. on other pages), send directly
    confirmSOS();
  }
}

async function confirmSOS() {
  sosActive = true;
  
  if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(async (position) => {
      const location = {
        lat: position.coords.latitude,
        lng: position.coords.longitude
      };
      
      const result = await apiCall('../backend/sos/send_sos.php', 'POST', location);
      
      if (result && result.success) {
        showNotification('🚨 SOS Alert Sent! Emergency services notified.', 'danger');
        // Actually call emergency number if confirmed
        window.location.href = "tel:100";
      }
      
      const modalElement = document.getElementById('emergencyModal');
      if (modalElement) {
        const modal = bootstrap.Modal.getInstance(modalElement);
        if (modal) modal.hide();
      }
      
      setTimeout(() => { sosActive = false; }, 30000);
    }, (error) => {
      showNotification('❌ Unable to get location. SOS sent without location data.', 'warning');
      apiCall('../backend/sos/send_sos.php', 'POST', {});
    });
  } else {
    showNotification('❌ Geolocation not supported.', 'danger');
  }
}

function emergencyCall() {
  showNotification('📞 Dialing emergency number...', 'info');
  window.location.href = "tel:100"; // Standard emergency number in India
}

function shareLocation() {
  if (navigator.geolocation) {
    showNotification('📍 Getting your current location...', 'info');
    navigator.geolocation.getCurrentPosition((position) => {
      const { latitude, longitude } = position.coords;
      showNotification(`✅ Location shared: ${latitude.toFixed(4)}, ${longitude.toFixed(4)}`, 'success');
      // In a real app, this would call a backend to share with contacts
      apiCall('../backend/sos/share_location.php', 'POST', { lat: latitude, lng: longitude });
    }, (error) => {
      showNotification('❌ Unable to share location: ' + error.message, 'danger');
    });
  } else {
    showNotification('❌ Geolocation is not supported by your browser', 'danger');
  }
}

// Incident Reporting
async function submitReport(event) {
  event.preventDefault();
  const incident = document.getElementById('incident').value;
  const date = new Date().toISOString().split('T')[0];
  const time = new Date().toTimeString().split(' ')[0];
  
  if (incident.trim()) {
    const result = await apiCall('../backend/reports/add_report.php', 'POST', {
      incident_type: 'Other',
      date: date,
      time: time,
      location_address: 'Current Location',
      description: incident
    });
    
    if (result && result.success) {
      showNotification('✅ Incident report submitted successfully!', 'success');
      setTimeout(() => {
        window.location.href = 'dashboard.html';
      }, 1500);
    }
  } else {
    showNotification('❌ Please describe the incident', 'danger');
  }
}

// Contact Management
async function addEmergencyContact(name, phone, relationship) {
  const result = await apiCall('../backend/contacts/add_contact.php', 'POST', { name, phone, relationship });
  if (result && result.success) {
    showNotification(`✅ ${name} added successfully`, 'success');
    loadContacts();
  }
}

async function removeEmergencyContact(id) {
  const result = await apiCall('../backend/contacts/delete_contact.php', 'POST', { id });
  if (result && result.success) {
    showNotification('✅ Contact removed', 'success');
    loadContacts();
  }
}

async function loadContacts() {
  const result = await apiCall('../backend/contacts/get_contacts.php');
  if (result && result.success) {
    emergencyContacts = result.data;
    renderContacts();
  }
}

function renderContacts() {
  const contactList = document.getElementById('contactsList');
  if (!contactList) return;
  
  const countBadge = document.getElementById('contactCount');
  if (countBadge) {
    countBadge.textContent = `${emergencyContacts.length} contacts`;
  }
  
  if (emergencyContacts.length === 0) {
    contactList.innerHTML = '<p class="text-center text-muted">No emergency contacts added yet.</p>';
    return;
  }
  
  contactList.innerHTML = emergencyContacts.map(contact => `
    <div class="contact-item d-flex align-items-center justify-content-between p-3 border rounded mb-3">
      <div class="d-flex align-items-center">
        <div class="contact-avatar me-3">
          <i class="fas fa-user-circle fa-2x text-primary"></i>
        </div>
        <div>
          <h6 class="mb-1">${contact.name}</h6>
          <p class="mb-1 text-muted">${contact.phone}</p>
          <small class="badge bg-info">${contact.relationship}</small>
        </div>
      </div>
      <div class="contact-actions">
        <button class="btn btn-sm btn-outline-primary me-2" onclick="safetyApp.testCall('${contact.name}', '${contact.phone}')">
          <i class="fas fa-phone"></i> Test Call
        </button>
        <button class="btn btn-sm btn-outline-success me-2" onclick="safetyApp.testSMS('${contact.name}', '${contact.phone}')">
          <i class="fas fa-sms"></i> Test SMS
        </button>
        <button class="btn btn-sm btn-outline-danger" onclick="safetyApp.removeEmergencyContact(${contact.id})">
          <i class="fas fa-trash"></i>
        </button>
      </div>
    </div>
  `).join('');
}

function testCall(name, phone) {
  showNotification(`📞 Initiating test call to ${name}...`, 'info');
  // On mobile devices, this will open the dialer
  window.location.href = `tel:${phone}`;
}

function testSMS(name, phone) {
  const message = `Hello ${name}, this is a test emergency message from SafetyGuard.`;
  showNotification(`💬 Preparing test SMS to ${name}...`, 'success');
  // On mobile devices, this will open the SMS app with a pre-filled message
  window.location.href = `sms:${phone}?body=${encodeURIComponent(message)}`;
}

function callEmergency(number, service) {
  showNotification(`📞 Calling ${service} (${number})...`, 'info');
  window.location.href = `tel:${number}`;
}

// Utility Functions
function showNotification(message, type = 'info') {
  const notification = document.createElement('div');
  notification.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
  notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
  notification.innerHTML = `
    ${message}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  `;
  document.body.appendChild(notification);
  setTimeout(() => { if (notification.parentNode) notification.remove(); }, 5000);
}

// Initialize app
function initializeApp() {
  const protectedPages = ['dashboard.html', 'contacts.html', 'profile.html', 'report.html'];
  const isProtected = protectedPages.some(page => window.location.pathname.includes(page));
  
  if (isProtected && !currentUser) {
    window.location.href = 'login.html';
    return;
  }

  // Update welcome message if on dashboard
  const welcomeName = document.getElementById('welcome-name');
  if (welcomeName && currentUser) {
    welcomeName.textContent = currentUser.name;
  }

  if (window.location.pathname.includes('dashboard.html') || window.location.pathname.includes('contacts.html')) {
    loadContacts();
  }
}

document.addEventListener('DOMContentLoaded', initializeApp);

// Export functions
window.safetyApp = {
  loginUser,
  registerUser,
  logoutUser,
  sendSOS,
  confirmSOS,
  shareLocation,
  emergencyCall,
  callEmergency,
  testCall,
  testSMS,
  submitReport,
  addEmergencyContact,
  removeEmergencyContact,
  showNotification
};
