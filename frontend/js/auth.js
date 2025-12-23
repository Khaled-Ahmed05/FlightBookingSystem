function showAlert(type, msg) {
  let div = $('<div class="alert"></div>').addClass(type).text(msg);
  $('body').prepend(div);
  div.fadeIn().delay(3000).fadeOut();
}

function register(formData, callback) {
  $.ajax({
    url: '../../backend/api/auth/register.php',
    method: 'POST',
    contentType: 'application/json',
    data: JSON.stringify(formData),
    success: callback
  });
}

function login(formData, callback) {
  $.ajax({
    url: '../../backend/api/auth/login.php',
    method: 'POST',
    contentType: 'application/json',
    data: JSON.stringify(formData),
    success: callback
  });
}
