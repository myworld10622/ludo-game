module.exports = (sequelize, DataTypes) => {
    const RummyPointLog = sequelize.define("tbl_rummy_log", {
        id: {
            type: DataTypes.INTEGER,
            primaryKey: true,
            autoIncrement: true
        },
        game_id: {
            type: DataTypes.INTEGER,
        },
        user_id: {
            type: DataTypes.INTEGER,
        },
        action: {
            type: DataTypes.INTEGER,
        },
        json: {
            type: DataTypes.TEXT,
        },
        seen: {
            type: DataTypes.TINYINT,
        },
        amount: {
            type: DataTypes.BIGINT,
        },
        points: {
            type: DataTypes.INTEGER,
        },
        timeout: {
            type: DataTypes.TINYINT,
        },
        added_date: {
            type: DataTypes.DATE,
            allowNull: false,
            defaultValue: DataTypes.NOW
        }
    }, {
        tableName: 'tbl_rummy_log',
        timestamps: true,
        createdAt: 'added_date',
        updatedAt: false,
        indexes: [
            {
                name: 'idx_game_id',
                fields: ['game_id']
            }
        ]
    })

    return RummyPointLog;
}