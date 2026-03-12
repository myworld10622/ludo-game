class ErrorHandler {
    logError(error) {
        const { name, message, stack } = error;
        // Log Error here
        console.log("******************************",name);
        console.log("******************************",message);
        console.log("******************************",stack);
    }

    handle(error, req, res, next) {
        this.logError(error);
        // res.status(500).json({ error: 'Internal Server Error' });
    }
}

module.exports = new ErrorHandler();