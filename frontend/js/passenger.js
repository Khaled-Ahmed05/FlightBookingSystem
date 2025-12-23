function setField(id, value) {
  const el = $('#' + id);
  if (!el.length) return;

  if (el.is('input, textarea, select')) {
    el.val(value);
  } else {
    el.text(value);
  }
}

function loadPassengerProfile() {
  $.getJSON('../../backend/api/passenger/profile.php', function (res) {
    if (!res.success) return;

    setField('name', res.data.name);
    setField('email', res.data.email);
    setField('tel', res.data.tel);
    setField('account_balance', res.data.account_balance);

    if (res.data.photo) {
      $('#photo').attr(
        'src',
        '../../backend/uploads/passengers/' + res.data.photo
      );
    }

    if (res.data.passport_img) {
      $('#passport').attr(
        'src',
        '../../backend/uploads/passports/' + res.data.passport_img
      );
    }
  });
}

function loadPassengerFlights() {
  $.getJSON('../../backend/api/passenger/flights.php', function (res) {

    if (res.success) {
      let current = '';
      let completed = '';

      res.data.current.forEach(f => {
        let row = `<tr><td>${f.name}</td><td>${f.fees}</td><td>${f.booking_status}</td><td>${f.itinerary[0].start_time}</td></tr>`;
        if (f.status === 'completed') completed += row;
        else current += row;
      });

      res.data.completed.forEach(f => {
        let row = `<tr><td>${f.name}</td><td>${f.fees}</td><td>${f.booking_status}</td><td>${f.itinerary[0].start_time}</td></tr>`;
        if (f.status === 'completed') completed += row;
        else current += row;
      });

      $('#currentFlights').html(current);
      $('#completedFlights').html(completed);
    }
  });
}

function searchFlights(from, to) {
  $.getJSON('../../backend/api/passenger/search_flights.php', { from, to }, function (res) {
    if (res.success) {
      let tbody = '';
      res.data.forEach(f => {
        tbody += `<tr onclick="window.location.href='flight_info.html?flight_id=${f.id}'">
          <td>${f.name}</td><td>${f.fees}</td><td>${f.max_passengers}</td>
        </tr>`;
      });
      $('#resultsTable').html(tbody);
    }
  });
}

function showAlert(message, type = 'info') {
  const alertBox = $(`<div class="alert alert-${type}">${message}</div>`);
  $('#alertContainer').append(alertBox);
  setTimeout(() => {
    alertBox.fadeOut(500, function () { $(this).remove(); });
  }, 3000);
}
