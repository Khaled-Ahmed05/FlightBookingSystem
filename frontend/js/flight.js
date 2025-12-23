function getQueryParam(param) {
  return new URLSearchParams(window.location.search).get(param);
}

function loadFlightInfo(flightId, callback) {
  $.getJSON('../../backend/api/flight/info.php', { flight_id: flightId }, function (res) {
    if (res.success) {
      let f = res.data;

      $('#flightName').text(f.name);
      $('#flightId').text(f.flight_id);
      $('#fees').text(f.fees);
      $('#max').text(f.max_passengers);

      let itineraryHTML = '';
      if (f.itinerary && Array.isArray(f.itinerary)) {
        f.itinerary.forEach(i => {
          itineraryHTML += `<tr><td>${i.city}</td><td>${i.start_time}</td><td>${i.end_time}</td></tr>`;
        });
      }
      $('#itineraryTable tbody').html(itineraryHTML);

      let passengersHTML = '';
      if (f.registered_passengers && Array.isArray(f.registered_passengers)) {
        f.registered_passengers.forEach(p => {
          passengersHTML += `<ul> <li>${p.name}</li> </ul>`;
        });
      } else {
        passengersHTML = '<p>No registered passengers yet.</p>';
      }
      $('#passengersList').html(passengersHTML);

      if (callback) callback(f);
    }
  });
}

function takeFlight(flightId) {
  $(`<div>How do you want to pay for this flight? </div>`).dialog({
    title: "Confirm Booking",
    modal: true,
    buttons: {
      "Pay from Account": function () {
        bookFlight(flightId, 'account');
        $(this).dialog("close");
      },
      "Pay Cash": function () {
        bookFlight(flightId, 'cash');
        $(this).dialog("close");
      },
      Cancel: function () {
        $(this).dialog("close");
      }
    }
  });
}

function bookFlight(flightId, paymentMethod) {
  $.ajax({
    url: '../../backend/api/passenger/book_flight.php',
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    data: JSON.stringify({
      flight_id: parseInt(flightId),
      payment_method: paymentMethod
    }),
    success: function (res) {
      if (res.success) {
        window.location.href = 'passenger_home.html';
      }
    },
    error: function (xhr) {
      console.error("RAW RESPONSE:", xhr.responseText);
      alert("Booking failed");
    }
  });
}


