/* Safe Feather Icons replacement - waits for library if not ready */
function renderIcons() {
if (typeof feather !== 'undefined' && feather.replace) {
feather.replace();
} else {
setTimeout(renderIcons, 50);
}
}

/* Navigation */
function go(id) {
document.querySelectorAll('.page').forEach(p => p.classList.remove('active'));
document.getElementById('pg-' + id).classList.add('active');
document.querySelectorAll('.hdr-nav button:not(.hdr-cta)').forEach(b => b.classList.remove('act'));
const nb = document.getElementById('nb-' + id);
if (nb) 
nb.classList.add('act');



window.scrollTo({top: 0, behavior: 'smooth'});
renderIcons();
}

/* Contact Form */
function submitForm() {
document.getElementById('successBox').classList.add('on');
setTimeout(() => document.getElementById('successBox').classList.remove('on'), 5000);
}

/* Booking Modal */
let bookingData = {
service: '',
icon: '',
price: '',
tag: '',
date: '',
time: '',
fname: '',
lname: '',
phone: '',
email: '',
brand: '',
model: '',
issue: ''
};
let currentStep = 1;

document.addEventListener('DOMContentLoaded', function () {
const today = new Date().toISOString().split('T')[0];
document.getElementById('bm-date').setAttribute('min', today);
renderIcons();
});

function openBooking(service, icon, price, tag) {
currentStep = 1;
bookingData = {
service,
icon,
price,
tag,
date: '',
time: '',
fname: '',
lname: '',
phone: '',
email: '',
brand: '',
model: '',
issue: ''
};
document.getElementById('bm-title').textContent = service || 'Book a Repair';
document.getElementById('bm-icon').innerHTML = icon || '<i data-feather="tool"></i>';
document.getElementById('bm-subtitle').textContent = price ? price + ' · Free diagnostic included' : 'Fill in your details below.';
document.getElementById('bm-badge').innerHTML = tag ? '<i data-feather="settings"></i> ' + tag : '<i data-feather="settings"></i> Book a Repair';
document.querySelectorAll('.bm-svc-chip').forEach(c => {
c.classList.remove('selected');
if (service && c.dataset.service === service) 
c.classList.add('selected');



});
showPanel(1);
updateProgress(1);
checkStep1();
document.getElementById('bookingOverlay').classList.add('active');
document.body.style.overflow = 'hidden';
renderIcons();
}
function closeBooking() {
document.getElementById('bookingOverlay').classList.remove('active');
document.body.style.overflow = '';
}
function closeBookingOnBg(e) {
if (e.target.id === 'bookingOverlay') 
closeBooking();



}
function showPanel(n) {
document.querySelectorAll('.bm-panel').forEach(p => p.classList.remove('active'));
document.getElementById('panel-' + (
n === 'success' ? 'success' : n
)).classList.add('active');
}
function updateProgress(step) {
for (let i = 1; i <= 3; i++) {
const el = document.getElementById('prog-' + i);
el.classList.remove('active', 'done');
if (i < step) 
el.classList.add('done');
 else if (i === step) 
el.classList.add('active');



}
for (let i = 1; i <= 2; i++) {
document.getElementById('line-' + i).classList.toggle('done', i < step);
}
}
function selectService(chip) {
document.querySelectorAll('.bm-svc-chip').forEach(c => c.classList.remove('selected'));
chip.classList.add('selected');
bookingData.service = chip.dataset.service;
bookingData.icon = chip.dataset.icon;
bookingData.price = chip.dataset.price;
bookingData.tag = chip.dataset.tag;
document.getElementById('bm-title').textContent = bookingData.service;
document.getElementById('bm-icon').innerHTML = '<i data-feather="' + (
bookingData.icon || 'tool'
) + '"></i>';
renderIcons();
document.getElementById('bm-subtitle').textContent = bookingData.price + ' · Free diagnostic included';
checkStep1();
}
function checkStep1() {
const hasService = document.querySelector('.bm-svc-chip.selected') !== null;
document.getElementById('step1-next').disabled = ! hasService;
}
document.getElementById('bm-date').addEventListener('change', checkStep1);
document.getElementById('bm-time-select').addEventListener('change', checkStep1);

function goToStep(n) {
if (n === 2) {
bookingData.date = document.getElementById('bm-date').value;
bookingData.time = document.getElementById('bm-time-select').value;
}
if (n === 3) {
bookingData.fname = document.getElementById('bm-fname').value.trim();
bookingData.lname = document.getElementById('bm-lname').value.trim();
bookingData.phone = document.getElementById('bm-phone').value.trim();
bookingData.email = document.getElementById('bm-email').value.trim();
bookingData.brand = document.getElementById('bm-brand').value;
bookingData.model = document.getElementById('bm-model').value.trim();
bookingData.issue = document.getElementById('bm-issue').value.trim();
if (! bookingData.fname || ! bookingData.phone) {
document.getElementById('bm-fname').focus();
document.getElementById('bm-fname').style.borderColor = 'var(--or1)';
return;
}
populateReview();
}
currentStep = n;
showPanel(n);
updateProgress(n);
document.getElementById('bookingModal').scrollTop = 0;
}
function populateReview() {
const dateStr = bookingData.date ? new Date(bookingData.date).toLocaleDateString('en-PH', {
weekday: 'short',
year: 'numeric',
month: 'long',
day: 'numeric'
}) : 'Not specified';
document.getElementById('rev-service').textContent = bookingData.service || '—';
document.getElementById('rev-price').textContent = bookingData.price || 'TBD after free diagnostic';
document.getElementById('rev-datetime').textContent = bookingData.date ? dateStr + (bookingData.time ? ', ' + bookingData.time : '') : 'Walk-in';
document.getElementById('rev-name').textContent = (bookingData.fname + ' ' + bookingData.lname).trim() || '—';
document.getElementById('rev-phone').textContent = bookingData.phone || '—';
document.getElementById('rev-device').textContent = [bookingData.brand, bookingData.model].filter(Boolean).join(' ') || 'Not specified';
document.getElementById('rev-issue').textContent = bookingData.issue || 'Not specified';
}
function confirmBooking() {
const ref = 'ONR-' + Date.now().toString().slice(-6);
document.getElementById('bm-ref-num').textContent = ref;
showPanel('success');
for (let i = 1; i <= 3; i++) {
const el = document.getElementById('prog-' + i);
el.classList.remove('active');
el.classList.add('done');
}
document.getElementById('line-1').classList.add('done');
document.getElementById('line-2').classList.add('done');
document.getElementById('bookingModal').scrollTop = 0;
}
document.addEventListener('keydown', function (e) {
if (e.key === 'Escape') {
closeBooking();
}
});