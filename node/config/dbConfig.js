module.exports={
    HOST:process.env.DB_HOST,
    PORT:process.env.DB_PORT,
    USER:process.env.DB_USERNAME,
    PASSWORD:process.env.DB_PASSWORD,
    DB:process.env.DB_NAME,
    dateStrings: true,
    dialect:'mysql'
}