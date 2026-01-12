// Compute API base URL dynamically so the frontend works regardless of host/folder
const API_BASE_URL = (function () {
    // Example result: https://localhost/SportClubFacilitiesBooking
    try {
        const origin = window.location.origin;
        const path = window.location.pathname;
        const basePath = path.substring(0, path.lastIndexOf('/')) || '';
        return origin + basePath;
    } catch (e) {
        // fallback to root folder
        return '';
    }
})();

// Helper: format a Date object as YYYY-MM-DD in local time (avoids UTC shifting)
function formatDateLocal(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

// ===== BACKEND TOGGLE =====
// Set this to false to completely disconnect the booking page from the database.
// When false, the page uses static demo data and does NOT call any PHP endpoints.
const USE_BACKEND = true;

// Static demo facilities used when USE_BACKEND === false
const DEMO_FACILITIES = [
    {
        title: 'Basketball Court',
        image: 'images/Basketball.png',
        status: 'open',
        venue_ids: ['DEMO_BASKET_1', 'DEMO_BASKET_2'],
        courts: 2
    },
    {
        title: 'Badminton Court',
        image: 'images/BadmintonBall.png',
        status: 'open',
        venue_ids: ['DEMO_BAD_1', 'DEMO_BAD_2', 'DEMO_BAD_3'],
        courts: 3
    },
    {
        title: 'Futsal Court',
        image: 'images/SoccerBall.png',
        status: 'open',
        venue_ids: ['DEMO_FUTSAL_1'],
        courts: 1
    }
];
// ===== FACILITY DATA =====
let facilities = [];

let currentIndex = 0;
let currentCalendarDate = new Date();
let selectedDate = null;
// Stores all dates (YYYY-MM-DD) that have events for the currently selected facility
let facilityEventDates = new Set();

// DOM elements
const track = document.getElementById("track");
const titleEl = document.getElementById("facility-title");
const courtContainer = document.getElementById("courtContainer");
const timeContainer = document.getElementById("timeContainer");
const selectedCourtInput = document.getElementById("selectedCourt");
const selectedTimeInput = document.getElementById("selectedTime");
const bookBtn = document.getElementById("bookBtn");
const bookingIdBox = document.getElementById("bookingIdBox");
const bookingIdList = document.getElementById("bookingIdList");
const copyBookingIdsBtn = document.getElementById("copyBookingIds");

// ===== TIME SLOTS =====
const MAX_HOURS = 2;
let selectedTimes = [];

const timeSlots = [
    "07:00 AM", "08:00 AM", "09:00 AM", "10:00 AM", "11:00 AM", "12:00 PM",
    "01:00 PM", "02:00 PM", "03:00 PM", "04:00 PM", "05:00 PM", "06:00 PM",
    "07:00 PM", "08:00 PM", "09:00 PM"
];
// Helper: parse a time string into 24-hour HH:MM (returns null on failure)
function parseTimeTo24(t) {
    if (!t) return null;
    const s = String(t).trim();
    const m = s.match(/^(\d{1,2}):(\d{2})(?::(\d{2}))?\s*(AM|PM)?$/i);
    if (!m) return null;
    let hh = parseInt(m[1], 10);
    const mm = parseInt(m[2], 10);
    const period = m[4] ? m[4].toUpperCase() : null;

    if (period) {
        if (period === 'AM' && hh === 12) hh = 0;
        if (period === 'PM' && hh !== 12) hh += 12;
    }

    return `${String(hh).padStart(2, '0')}:${String(mm).padStart(2, '0')}`;
}

// Helper: format any supported time string as 12-hour with leading zero hour (e.g. "01:00 PM")
function format12Leading(t) {
    const twenty = parseTimeTo24(t);
    if (!twenty) return String(t).trim();
    const parts = twenty.split(':');
    const hh = parseInt(parts[0], 10);
    const mm = parts[1];
    const period = hh >= 12 ? 'PM' : 'AM';
    let h12 = hh % 12;
    if (h12 === 0) h12 = 12;
    return `${String(h12).padStart(2, '0')}:${mm} ${period}`;
}
// ===== LOAD VENUES (FROM DATABASE OR STATIC DEMO) =====
async function loadVenues() {
    // If backend is disabled, just use static demo data
    if (!USE_BACKEND) {
        facilities = DEMO_FACILITIES;
        renderCards();
        updateFacilityInfo();

        // If only one facility is shown, hide carousel nav buttons and disable keyboard carousel
        if (facilities.length === 1) {
            const prev = document.querySelector('.nav-btn.prev');
            const next = document.querySelector('.nav-btn.next');
            if (prev) prev.style.display = 'none';
            if (next) next.style.display = 'none';
            document.removeEventListener('keydown', carouselKeyHandler);
            try { document.body.classList.add('single-facility'); } catch (e) {}
        }
        return;
    }

    try {
        const response = await fetch(`${API_BASE_URL}/get_venues.php`);
        const result = await response.json();
        
        if (result.success && result.data.length > 0) {
            // Group database rows by facility title so each facility shows as one card
            const groups = {};
            result.data.forEach(venue => {
                const key = (venue.title || venue.facility_type || '').trim();
                if (!key) return;
                if (!groups[key]) {
                    groups[key] = {
                        title: key,
                        image: venue.image || 'fac2.jpg',
                        status: venue.status || 'open', // Default to 'open' if status is null
                        venue_ids: []
                    };
                }
                // push the underlying venue_id (one per court row)
                if (venue.venue_id && !groups[key].venue_ids.includes(venue.venue_id)) {
                    groups[key].venue_ids.push(venue.venue_id);
                }
                // If any venue in the group is closed, mark the entire facility as closed
                if (venue.status && venue.status === 'closed') {
                    groups[key].status = 'closed';
                }
            });

            const all = Object.keys(groups).map(k => ({
                title: groups[k].title,
                image: groups[k].image,
                status: groups[k].status,
                venue_ids: groups[k].venue_ids,
                courts: groups[k].venue_ids.length
            }));

            // If page requested a specific venue via query param, show only that one
            const urlParams = new URLSearchParams(window.location.search);
            const requestedId = urlParams.get('venue_id') || urlParams.get('id') || null;
            const requestedType = urlParams.get('type') || urlParams.get('facility') || null;

            if (requestedId) {
                const found = all.find(v => String(v.venue_id) === String(requestedId));
                if (found) {
                    facilities = [found];
                } else {
                    showWarning('Requested facility not found. Showing all facilities instead.');
                    facilities = all;
                }
            } else if (requestedType) {
                // Find the first venue that matches the requested type or contains the term in title
                const typeLower = requestedType.toLowerCase();
                const found = all.find(v => {
                    const title = (v.title || '').toLowerCase();
                    const ftype = (v.facility_type || '').toLowerCase();
                    return ftype === typeLower || title === typeLower || title.includes(typeLower);
                });
                if (found) {
                    facilities = [found];
                } else {
                    showWarning('No facility of that type found. Showing all facilities instead.');
                    facilities = all;
                }
            } else {
                facilities = all;
            }

            renderCards();
            updateFacilityInfo();

            // If only one facility is shown, hide carousel nav buttons and disable keyboard carousel
            if (facilities.length === 1) {
                const prev = document.querySelector('.nav-btn.prev');
                const next = document.querySelector('.nav-btn.next');
                if (prev) prev.style.display = 'none';
                if (next) next.style.display = 'none';
                // prevent arrow keys from moving slides when single
                document.removeEventListener('keydown', carouselKeyHandler);
                // add body class for CSS single-facility layout (also handled server-side)
                try { document.body.classList.add('single-facility'); } catch (e) {}
            }
        } else {
            showWarning('No facilities found. Please check if the database is properly configured.');
        }
    } catch (error) {
        console.error('Error:', error);
        showWarning('Failed to connect to the server. Please check if XAMPP is running.');
    }
}



// ===== CHECK AVAILABILITY =====
let bookedTimes = [];

async function checkAvailability(venueId, date) {
    if (!venueId || !date) return;

    const formattedDate = formatDateLocal(date);

    // When backend is disabled, simply clear bookedTimes so all slots appear free
    if (!USE_BACKEND) {
        bookedTimes = [];
        renderTimeButtons();
        return;
    }

    try {
        // Determine whether the identifier is a numeric court_id or a legacy venue_id
        const isNumeric = String(venueId).match(/^\d+$/);
        const param = isNumeric ? `court_id=${venueId}` : `venue_id=${encodeURIComponent(venueId)}`;

        const response = await fetch(`${API_BASE_URL}/check_availability.php?${param}&date=${formattedDate}`);
        const result = await response.json();

        if (result.success) {
            bookedTimes = result.data.booked_times; // Now includes type information
            renderTimeButtons(); // Re-render with booked times disabled
        } else {
            console.error('Availability check error:', result.message);
        }
    } catch (error) {
        console.error('Error checking availability:', error);
    }
}

// ===== CALENDAR FUNCTIONS =====
const monthNames = ["January", "February", "March", "April", "May", "June",
    "July", "August", "September", "October", "November", "December"];

function openCalendar() {
    document.getElementById('calendarPopup').style.display = 'flex';
    renderCalendar();
}

function closeCalendar() {
    document.getElementById('calendarPopup').style.display = 'none';
}

function renderCalendar() {
    const year = currentCalendarDate.getFullYear();
    const month = currentCalendarDate.getMonth();
    
    document.getElementById('calendarMonthYear').textContent = `${monthNames[month]} ${year}`;

    const firstDay = new Date(year, month, 1);
    const lastDay = new Date(year, month + 1, 0);
    const prevLastDay = new Date(year, month, 0);
    
    const firstDayIndex = firstDay.getDay();
    const lastDateOfMonth = lastDay.getDate();
    const prevLastDate = prevLastDay.getDate();

    const calendarDays = document.getElementById('calendarDays');
    calendarDays.innerHTML = '';

    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const isCurrentMonth = today.getFullYear() === year && today.getMonth() === month;

    // Previous month's days
    for (let i = firstDayIndex; i > 0; i--) {
        const day = createDayElement(prevLastDate - i + 1, true, false);
        calendarDays.appendChild(day);
    }

    // Current month's days
    for (let i = 1; i <= lastDateOfMonth; i++) {
        const date = new Date(year, month, i);
        date.setHours(0, 0, 0, 0);
        const isPast = date < today;
        const isToday = isCurrentMonth && i === today.getDate();
        const isEventDay =
            !isPast &&
            facilityEventDates instanceof Set &&
            facilityEventDates.has(formatDateLocal(date));
        const day = createDayElement(i, false, isPast, isToday, date, isEventDay);
        calendarDays.appendChild(day);
    }

    // Next month's days
    const remainingDays = 42 - (firstDayIndex + lastDateOfMonth);
    for (let i = 1; i <= remainingDays; i++) {
        const day = createDayElement(i, true, false);
        calendarDays.appendChild(day);
    }
}

function createDayElement(dayNum, isOtherMonth, isPast, isToday = false, date = null, isEventDay = false) {
    const day = document.createElement('div');
    day.className = 'calendar-day';
    day.textContent = dayNum;

    if (isOtherMonth) {
        day.classList.add('other-month');
    } else if (isPast) {
        day.classList.add('disabled');
    } else {
        // Mark days that have events and prevent booking on those days
        if (isEventDay) {
            day.classList.add('event-day', 'disabled');
            day.title = 'This day has an event and cannot be booked.';
            day.onclick = () => {
                showWarning('This day has an event and cannot be booked.');
            };
            return day;
        }

        if (isToday) {
            day.classList.add('today');
        }

        if (selectedDate && date && 
            selectedDate.getDate() === date.getDate() &&
            selectedDate.getMonth() === date.getMonth() &&
            selectedDate.getFullYear() === date.getFullYear()) {
            day.classList.add('selected');
        }

        day.onclick = () => selectDate(date);
    }

    return day;
}

function selectDate(date) {
    selectedDate = date;
    
    // Update display (defensively check elements so a missing element
    // doesn't prevent the calendar from closing)
    const options = { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric' };
    const selectedDateTextEl = document.getElementById('selectedDateText');
    const selectedDateValueEl = document.getElementById('selectedDateValue');
    if (selectedDateTextEl) {
        selectedDateTextEl.textContent = date.toLocaleDateString('en-US', options);
    }
    if (selectedDateValueEl) {
        selectedDateValueEl.value = formatDateLocal(date);
    }

    // Close calendar overlay so the user returns to the booking view
    closeCalendar();
    
    // Check availability for the selected facility and date
    // If a specific court (venue_id) is selected, check availability for it
    const selectedVid = selectedCourtInput.value;
    if (selectedVid) {
        checkAvailability(selectedVid, date);
    }
    
    // Re-render to show selection
    renderCalendar();
}

document.getElementById('prevMonth').onclick = () => {
    currentCalendarDate.setMonth(currentCalendarDate.getMonth() - 1);
    renderCalendar();
};

document.getElementById('nextMonth').onclick = () => {
    currentCalendarDate.setMonth(currentCalendarDate.getMonth() + 1);
    renderCalendar();
};

// ===== RENDER TIME BUTTONS =====
function renderTimeButtons() {
    timeContainer.innerHTML = "";
    selectedTimes = [];
    selectedTimeInput.value = "";

    timeSlots.forEach(time => {
        const btn = document.createElement("button");
        // Always display with leading zero like "01:00 PM"
        btn.textContent = format12Leading(time);
        btn.className = "time-btn";
        
        // Normalize to 24-hour HH:MM for reliable comparison
        const normalized24 = parseTimeTo24(time);

        // Determine if this time is booked. bookedTimes entries might be strings or objects.
        const isBooked = bookedTimes.some(btRaw => {
            const btVal = (typeof btRaw === 'string') ? btRaw : (btRaw && (btRaw.time || btRaw.slot || btRaw.value) ? (btRaw.time || btRaw.slot || btRaw.value) : '');
            const bt24 = parseTimeTo24(btVal);
            if (bt24 && normalized24) return bt24 === normalized24;
            // Fallback: compare normalized textual forms
            return String(btVal).replace(/\s+/g, ' ').trim().toUpperCase() === String(time).replace(/\s+/g, ' ').trim().toUpperCase();
        });
        
        if (isBooked) {
            btn.classList.add("booked");
            btn.disabled = true;
            btn.title = "This time slot is already booked by another user";
        }

        btn.onclick = () => {
            // Prevent clicking if already booked or disabled
            if (isBooked || btn.disabled) {
                if (isBooked) {
                    showWarning("This time slot is already booked by another user. Please select a different time.");
                }
                return;
            }
            
            const display = format12Leading(time);

            if (btn.classList.contains("active")) {
                btn.classList.remove("active");
                selectedTimes = selectedTimes.filter(t => t !== display);
            } else {
                if (selectedTimes.length >= MAX_HOURS) {
                    showWarning("Maximum booking: 2 hours");
                    return;
                }
                btn.classList.add("active");
                selectedTimes.push(display);
            }

            selectedTimeInput.value = selectedTimes.join(", ");
        };

        timeContainer.appendChild(btn);
    });
}

// ===== CENTER ACTIVE CARD =====
function centerActiveCard() {
    const cards = track.querySelectorAll('.facility-card');
    if (cards.length === 0) return;
    
    const wrapperWidth = track.parentElement.offsetWidth;
    const cardWidth = 200;
    const gap = 60;
    
    // Calculate the position of the active card in the track
    const activeCardOffset = currentIndex * (cardWidth + gap);
    
    // Center the active card in the viewport
    const translateX = (wrapperWidth / 1.45) - (cardWidth / 2) - activeCardOffset;
    
    track.style.transform = `translateX(${translateX}px)`;
}

// ===== RENDER FACILITY CARDS =====
function renderCards() {
    track.innerHTML = "";

    facilities.forEach((f, index) => {
        const card = document.createElement("div");
        card.className = "facility-card";
        card.style.backgroundImage = `url(${f.image})`;
        // mark closed facilities
        if (f.status && f.status === 'closed') {
            card.classList.add('closed');
        }

        const diff = Math.abs(index - currentIndex);

        if (index === currentIndex) {
            card.classList.add("active");
        } else if (diff === 1 || diff === facilities.length - 1) {
            card.classList.add("side");
        } else {
            card.classList.add("far");
        }

        card.onclick = () => {
            if (f.status && f.status === 'closed') {
                showWarning('This facility is currently closed and cannot be selected.');
                return;
            }
            currentIndex = index;
            renderCards();
        };

        track.appendChild(card);
    });

    centerActiveCard();
    updateFacilityInfo();
}

// ===== UPDATE FACILITY INFO =====
function updateFacilityInfo() {
    const facility = facilities[currentIndex];
    
    titleEl.style.opacity = '0';
    setTimeout(() => {
        titleEl.textContent = facility.title;
        titleEl.style.opacity = '1';
    }, 200);

    // Load events for this facility
    if (facility.venue_ids && facility.venue_ids.length > 0) {
        loadEventsForFacility(facility.venue_ids[0]); // Use first venue_id
    } else {
        renderEvents([]);
    }

    // If facility is closed, disable court/time selection
    if (facility.status && facility.status === 'closed') {
        renderCourtButtons([]);
        timeContainer.innerHTML = '<div class="closed-note">This facility is currently closed. Booking is disabled.</div>';
        bookBtn.disabled = true;
        bookBtn.classList.add('disabled');
    } else {
        renderCourtButtons(facility.venue_ids);
        renderTimeButtons();
        bookBtn.disabled = false;
        bookBtn.classList.remove('disabled');
    }
}

// ===== RENDER COURT BUTTONS =====
function renderCourtButtons(venueIds) {
    courtContainer.innerHTML = "";
    selectedCourtInput.value = "";

    // venueIds is an array of underlying venue_id values (one per court)
    if (!Array.isArray(venueIds) || venueIds.length === 0) return;
    venueIds.forEach((vid, idx) => {
        const btn = document.createElement("button");
        const label = `Court ${idx + 1}`;
        btn.textContent = label;
        btn.className = "court-btn";
        btn.dataset.venueId = vid;

        btn.onclick = async () => {
            document.querySelectorAll(".court-btn").forEach(b => b.classList.remove("active"));
            btn.classList.add("active");

            // If we already resolved a numeric court_id, use it; otherwise try to fetch court metadata
            if (btn.dataset.courtId) {
                selectedCourtInput.value = btn.dataset.courtId;
            } else {
                try {
                    const resp = await fetch(`${API_BASE_URL}/get_courts.php?venue_id=${encodeURIComponent(vid)}`);
                    const json = await resp.json();
                    if (json.success && Array.isArray(json.data) && json.data.length > 0) {
                        // Use the first matching court row for this venue
                        const court = json.data[0];
                        btn.dataset.courtId = court.court_id || '';
                        btn.textContent = court.name || label;
                        selectedCourtInput.value = court.court_id || vid;
                    } else {
                        // fallback to old venue_id string
                        selectedCourtInput.value = vid;
                    }
                } catch (e) {
                    console.error('Failed to fetch court metadata', e);
                    selectedCourtInput.value = vid;
                }
            }

            // after selecting a court, if a date is already chosen, check availability for that court
            if (selectedDate) {
                const idToCheck = btn.dataset.courtId ? btn.dataset.courtId : vid;
                checkAvailability(idToCheck, selectedDate);
            }
        };

        courtContainer.appendChild(btn);
    });
}

// ===== CAROUSEL NAVIGATION =====
function moveSlide(dir) {
    currentIndex += dir;
    if (currentIndex < 0) currentIndex = facilities.length - 1;
    if (currentIndex >= facilities.length) currentIndex = 0;
    renderCards();
}

// ===== WARNING POPUP =====
function showWarning(msg) {
    document.getElementById("warningMessage").textContent = msg;
    document.getElementById("warningPopup").style.display = "flex";
}

function closeWarning() {
    document.getElementById("warningPopup").style.display = "none";
}

// ===== CONFIRMATION POPUP =====
function showConfirmation() {
    const facility = facilities[currentIndex];
    const summaryHTML = `
        <p><span class="label">🏀 Facility:</span><span class="value">${facility.title}</span></p>
        <p><span class="label">🎯 Court:</span><span class="value">${document.querySelector('.court-btn.active') ? document.querySelector('.court-btn.active').textContent : selectedCourtInput.value}</span></p>
        <p><span class="label">📅 Date:</span><span class="value">${selectedDate.toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })}</span></p>
        <p><span class="label">🕐 Time:</span><span class="value">${selectedTimes.join(", ")}</span></p>
    `;
    
    document.getElementById('bookingSummary').innerHTML = summaryHTML;
    document.getElementById('confirmationPopup').style.display = 'flex';
}

function cancelBooking() {
    document.getElementById('confirmationPopup').style.display = 'none';
}

function confirmBooking() {
    const facility = facilities[currentIndex];
    const formattedDate = formatDateLocal(selectedDate);
    
    // Get the selected court button to check if we have court_id
    const selectedCourtBtn = document.querySelector('.court-btn.active');
    const courtIdValue = selectedCourtInput.value;
    
    // Determine if the value is a numeric court_id or a venue_id string
    const isNumericCourtId = /^\d+$/.test(courtIdValue);
    
    // When backend is disabled, skip saving and just show success popup
    if (!USE_BACKEND) {
        document.getElementById('confirmationPopup').style.display = 'none';
        const successPopup = document.getElementById('successPopup');
        if (successPopup) {
            successPopup.style.display = 'flex';
        }
        // Reset selections after a short delay
        setTimeout(() => {
            renderCards();
            selectedDate = null;
            document.getElementById('selectedDateText').textContent = 'Choose a date';
            selectedTimes = [];
            selectedTimeInput.value = '';
            document.querySelectorAll('.court-btn').forEach(btn => btn.classList.remove('active'));
        }, 2000);
        return;
    }

    // Prepare form data (backend enabled)
    const formData = new FormData();
    formData.append('facility', facility.title);
    
    // If we have a numeric court_id, send it as court_id; otherwise send as venue_id
    if (isNumericCourtId && selectedCourtBtn && selectedCourtBtn.dataset.courtId) {
        formData.append('court_id', courtIdValue);
        // Also send venue_id from the button's dataset for reference
        if (selectedCourtBtn.dataset.venueId) {
            formData.append('venue_id', selectedCourtBtn.dataset.venueId);
        }
    } else {
        // Legacy: send as venue_id
        formData.append('venue_id', courtIdValue);
    }
    
    formData.append('date', formattedDate);
    selectedTimes.forEach(time => formData.append('times[]', time));
    
    // Send booking to server
    fetch(`${API_BASE_URL}/save_booking.php`, {
        method: 'POST',
        body: formData
    })
    .then(response => {
        // Check if response is OK
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        document.getElementById('confirmationPopup').style.display = 'none';
        
        if (data.success) {
            console.log('Booking saved successfully:', data);
            document.getElementById('successPopup').style.display = 'flex';
            
            const ids = Array.isArray(data.booking_ids) ? data.booking_ids.filter(Boolean).map(String) : [];
            if (bookingIdBox && bookingIdList) {
                if (ids.length > 0) {
                    bookingIdList.textContent = ids.join(', ');
                    bookingIdBox.style.display = 'block';
                    if (copyBookingIdsBtn) {
                        copyBookingIdsBtn.disabled = false;
                        copyBookingIdsBtn.textContent = 'Copy IDs';
                        copyBookingIdsBtn.onclick = () => {
                            const text = ids.join(', ');
                            navigator.clipboard?.writeText(text).then(() => {
                                copyBookingIdsBtn.textContent = 'Copied!';
                                setTimeout(() => copyBookingIdsBtn.textContent = 'Copy IDs', 1200);
                            }).catch(() => {
                                alert('Copy not available. Please copy manually: ' + text);
                            });
                        };
                    }
                } else {
                    bookingIdList.textContent = '';
                    bookingIdBox.style.display = 'none';
                }
            }
            
            // Reset selections after a delay
            setTimeout(() => {
                renderCards();
                selectedDate = null;
                document.getElementById('selectedDateText').textContent = 'Choose a date';
                selectedTimes = [];
                selectedTimeInput.value = '';
                // Clear court selection
                document.querySelectorAll('.court-btn').forEach(btn => btn.classList.remove('active'));
            }, 2000);
        } else {
            // Show detailed error message
            let errorMsg = data.message || 'Failed to confirm booking. Please try again.';
            if (data.debug) {
                console.error('Booking error details:', data.debug);
                errorMsg += '\n\nDebug info: ' + JSON.stringify(data.debug);
            }
            console.error('Booking failed:', data);
            showWarning(errorMsg);
            document.getElementById('confirmationPopup').style.display = 'flex';
        }
    })
    .catch(error => {
        console.error('Network/Connection error:', error);
        showWarning('Connection error. Please check:\n1. XAMPP Apache is running\n2. XAMPP MySQL is running\n3. Database "campus_facility_booking" exists\n\nError: ' + error.message);
        document.getElementById('confirmationPopup').style.display = 'flex';
    });
}

function closeSuccess() {
    document.getElementById('successPopup').style.display = 'none';
    if (bookingIdBox && bookingIdList) {
        bookingIdBox.style.display = 'none';
        bookingIdList.textContent = '';
    }
}

// ===== BOOKING VALIDATION =====
bookBtn.onclick = () => {
    if (!selectedCourtInput.value) {
        showWarning("Please select a court.");
        return;
    }
    if (!selectedDate) {
        showWarning("Please select a date.");
        return;
    }
    if (selectedTimes.length === 0) {
        showWarning("Please select at least 1 time slot.");
        return;
    }

    showConfirmation();
};

// ===== KEYBOARD NAVIGATION =====
function carouselKeyHandler(e) {
    if (facilities.length > 1) {
        if (e.key === 'ArrowLeft') moveSlide(-1);
        if (e.key === 'ArrowRight') moveSlide(1);
    }
    if (e.key === 'Escape') {
        closeCalendar();
        closeWarning();
        cancelBooking();
        closeSuccess();
    }
}
document.addEventListener('keydown', carouselKeyHandler);

// ===== RESPONSIVE CAROUSEL ON RESIZE =====
window.addEventListener('resize', () => {
    centerActiveCard();
});

// ===== LOAD EVENTS FOR FACILITY =====
async function loadEventsForFacility(venueId) {
    // If backend is disabled, do not load events from server
    if (!USE_BACKEND) {
        renderEvents([]);
        return;
    }

    try {
        const response = await fetch(`get_events.php?venue_id=${venueId}`);
        const data = await response.json();
        
        if (data.success) {
            renderEvents(data.events);
        } else {
            console.error('Failed to load events:', data.message);
            renderEvents([]);
        }
    } catch (error) {
        console.error('Error loading events:', error);
        renderEvents([]);
    }
}

// ===== RENDER EVENTS =====
function renderEvents(events) {
    const eventsSection = document.getElementById('eventsSection');
    const eventsList = document.getElementById('eventsList');

    // Reset event dates for the currently selected facility
    facilityEventDates = new Set();
    if (Array.isArray(events)) {
        events.forEach(event => {
            if (!event.start_date) return;
            const start = new Date(event.start_date + 'T00:00:00');
            const end = new Date((event.end_date || event.start_date) + 'T00:00:00');
            // Ensure start <= end
            let current = start <= end ? start : end;
            const last = start <= end ? end : start;
            while (current <= last) {
                facilityEventDates.add(formatDateLocal(current));
                current.setDate(current.getDate() + 1);
            }
        });
    }

    if (events && events.length > 0) {
        eventsSection.style.display = 'block';
        eventsList.innerHTML = events.map(event => `
            <div class="event-item">
                <h4>${event.name}</h4>
                <p><strong>Date:</strong> ${formatDate(event.start_date)}</p>
                <p class="event-time"><strong>Time:</strong> ${event.start_time} - ${event.end_time}</p>
            </div>
        `).join('');
    } else {
        eventsSection.style.display = 'none';
    }

    // If the calendar is currently open, re-render so event days are disabled/marked
    const calendarPopup = document.getElementById('calendarPopup');
    if (calendarPopup && calendarPopup.style.display === 'flex') {
        renderCalendar();
    }
}

// ===== FORMAT DATE =====
function formatDate(dateStr) {
    const date = new Date(dateStr);
    return date.toLocaleDateString('en-US', { 
        weekday: 'long', 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric' 
    });
}

// ===== INITIAL LOAD =====
loadVenues();
titleEl.style.transition = 'opacity 0.3s ease';
