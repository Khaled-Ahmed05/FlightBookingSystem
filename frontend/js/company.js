function setField(id, value) {
  const el = $('#' + id);
  if (!el.length) return;

  if (el.is('input, textarea, select')) {
    el.val(value);
  } else {
    el.text(value);
  }
}

function loadCompanyProfile() {
  $.getJSON('../../backend/api/company/profile.php', function (res) {
    if (!res.success) return;

    setField('name', res.data.name);
    setField('bio', res.data.bio);
    setField('address', res.data.address);
    setField('location', res.data.location);
    setField('account_balance', res.data.account_balance);

    if (res.data.ogo) {
      $('#logo').attr(
        'src',
        '../../backend/uploads/logos/' + res.data.logo
      );
    }
    loadFlights(res.data.id);
  });
}

function loadFlights(companyId) {
  $.getJSON('../../backend/api/company/get_flights.php', function (res) {
    if (res.success) {
      let tbody = '';
      res.data.forEach(f => {
        if (f.is_completed === 0) f.is_completed = 'Pending';
        else f.is_completed = 'Cancelled';
        tbody += `<tr>
          <td>${f.id}</td>
          <td>${f.name}</td>
          <td>${f.registered_count}/${f.max_passengers}</td>
          <td>$${f.fees}</td>
          <td>${f.is_completed}</td>
          <td>
            <button onclick="window.location.href='flight_info.html?flight_id=${f.id}'">Details</button>
            <button onclick="cancelFlight(${f.id})">Cancel</button>
          </td>
        </tr>`;
      });
      $('#flightsTable tbody').html(tbody);
    }
  });
}

function cancelFlight(flightId) {
  $('<div>Are you sure you want to cancel this flight and refund passengers?</div>').dialog({
    title: "Confirm Cancel",
    modal: true,
    buttons: {
      Yes: function () {
        $.ajax({
          url: '../../backend/api/company/cancel_flight.php',
          method: 'POST',
          contentType: 'application/json',
          data: JSON.stringify({ flight_id: flightId }),
          dataType: 'json',
          success: function (res) {
            showAlert(res.success ? 'success' : 'error', res.message);
            loadFlights();
          }
        });
        $(this).dialog("close");
      },
      No: function () { $(this).dialog("close"); }
    }
  });
}

function addFlight() {
  let name = $('input[name="name"]').val();
  let fees = parseFloat($('input[name="fees"]').val());
  let max_passengers = parseInt($('input[name="max_passengers"]').val());

  let itinerary = [];
  $('#itineraryContainer .itineraryRow').each(function () {
    let city = $(this).find('input[name="city[]"]').val();
    let start_time = $(this).find('input[name="start[]"]').val();
    let end_time = $(this).find('input[name="end[]"]').val();

    itinerary.push({ city, start_time, end_time });
  });

  if (!name || isNaN(fees) || isNaN(max_passengers) || itinerary.length === 0) {
    alert("Please fill all fields properly");
    return;
  }

  $.ajax({
    url: '../../backend/api/company/add_flight.php',
    method: 'POST',
    contentType: 'application/json',
    data: JSON.stringify({
      name: name,
      fees: fees,
      max_passengers: max_passengers,
      itinerary: itinerary
    }),
    success: function (res) {
      if (res.success) {
        alert("Flight added successfully!");
        $('#addFlightModal').fadeOut();
        loadCompanyFlights();
      } else {
        alert("Error: " + res.message);
      }
    },
    error: function (xhr, status, err) {
      alert("AJAX Error: " + err);
      console.log(xhr.responseText);
    }
  });
}

function showAlert(type, message) {
  let alertDiv = $(`<div class="alert ${type}">${message}</div>`);
  $('body').append(alertDiv);
  alertDiv.fadeIn();
  setTimeout(() => { alertDiv.fadeOut(() => { alertDiv.remove(); }); }, 3000);
}