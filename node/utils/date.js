const moment = require('moment-timezone');

class DateHelper {
    timeToAmPM(dateInput) {
        const date = new Date(dateInput);

        // Extract date parts
        let hours = date.getHours();
        const minutes = date.getMinutes();
        const ampm = hours >= 12 ? 'PM' : 'AM';

        hours = hours % 12;
        hours = hours ? hours : 12; // the hour '0' should be '12'

        // Format minutes and seconds to always be 2 digits
        const formattedMinutes = minutes < 10 ? `0${minutes}` : minutes;

        // Format the date
        const formattedDate = `${hours}:${formattedMinutes} ${ampm}`;

        return formattedDate;
    }

    formatDateShort(dateInput) {
        const date = new Date(dateInput); // Current date, or you can pass a specific date here

        const day = date.getDate();
        const month = date.toLocaleString('default', { month: 'short' }); // 'Aug' for August

        // Format as "25 Aug"
        const formattedDate = `${day} ${month}`;

        return formattedDate;
    }

    get5MinutesAfterTime() {
        const timeIn5Minutes = moment().tz('Asia/Kolkata').add(5, 'minutes');

        return timeIn5Minutes.format('HH:mm:ss');
    }

    getCurrentTime() {
        return moment().tz('Asia/Kolkata').format('HH:mm:ss');
    }
}

module.exports = new DateHelper();