module.exports = (sequelize, DataTypes) => {
    const ColorPrediction = sequelize.define("tbl_color_prediction", {
        id: {
            type: DataTypes.INTEGER,
            primaryKey: true,
            autoIncrement: true
        },
        room_id: {
            type: DataTypes.INTEGER,
        },
        card1: {
            type: DataTypes.STRING(5),
        },
        card2: {
            type: DataTypes.STRING(5),
        },
        card3: {
            type: DataTypes.STRING(5),
        },
        winning: {
            type: DataTypes.TINYINT,
        },
        status: {
            type: DataTypes.TINYINT,
            defaultValue: 0
        },
        winning_amount: {
            type: DataTypes.FLOAT,
        },
        user_amount: {
            type: DataTypes.FLOAT
        },
        comission_amount: {
            type: DataTypes.FLOAT
        },
        total_amount: {
            type: DataTypes.FLOAT
        },
        admin_profit: {
            type: DataTypes.FLOAT
        },
        end_datetime: {
            type: DataTypes.DATE
        },
        random: {
            type: DataTypes.TINYINT
        },
        added_date: {
            type: DataTypes.DATE,
            allowNull: false,
            defaultValue: DataTypes.NOW
        },
        updated_date: {
            type: DataTypes.DATE,
            allowNull: false,
            defaultValue: DataTypes.NOW
        }
    }, {
        tableName: 'tbl_color_prediction',
        timestamps: true,
        createdAt: 'added_date',
        updatedAt: 'updated_date'
    })

    return ColorPrediction;
}