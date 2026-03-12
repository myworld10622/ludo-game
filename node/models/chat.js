module.exports = (sequelize, DataTypes) => {

    const Chat = sequelize.define("tbl_chat", {
        id: {
            type: DataTypes.INTEGER,
            primaryKey: true,
            autoIncrement: true
        },
        user_id: {
            type: DataTypes.INTEGER,
        },
        table_id: {
            type: DataTypes.INTEGER,
        },
        game_id: {
            type: DataTypes.INTEGER,
        },
        game: {
            type: DataTypes.STRING
        },
        chat: {
            type: DataTypes.INTEGER,
        },
        added_date: {
            type: DataTypes.DATE
        },
    }, {
        tableName: 'tbl_chat',
        timestamps: true,
        createdAt: 'added_date',
        updatedAt: false
    })
    return Chat
}