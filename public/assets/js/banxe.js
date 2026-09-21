const requiredSlots = [
    'front_left_45',
    'front_right_45',
    'rear_left_45',
    'rear_right_45',
    'front',
    'rear',
    'roof',

    'odometer',
    'cockpit',
    'driver_seat',
    'passenger_seat',
    'rear_seat_headliner',

    'engine_bay',
    'wheel_front_left',
    'wheel_front_right',
    'wheel_rear_left',
    'wheel_rear_right',

    'registration_front',
    'registration_back',
    'inspection_spec',
    'inspection_expiry'
];

const uploadedPhotos = new Map();
function updatedPhotoProgress(){
    const completed = requiredSlots.filter(
        slot=> uploadedPhotos.has(slot)
    ).length;
    const total = requiredSlots.length;
    const percent = Math.round(completed/total*100);
    document.getElementById('photoProgressText').textContent = `${completed} / ${total}`;
    document.getElementById('photoProgressBar').style.width = '$(percent)%';
    const continueButton = document.getElementById('continuebutton');
    continueButton.disabled = completed !==total;
}