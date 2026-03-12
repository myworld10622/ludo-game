module.exports = (sequelize, DataTypes) => {
    const SevenUp = sequelize.define("tbl_seven_up", {
        id: {
            type: DataTypes.INTEGER,
            primaryKey: true,
            autoIncrement: true
        },
        room_id: {
            type: DataTypes.INTEGER,
        },
        main_card: {
            type: DataTypes.STRING,
        },
        winning: {
            type: DataTypes.TINYINT,
        },
        status: {
            type: DataTypes.TINYINT,
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
        random_amount: {
            type: DataTypes.INTEGER,
            defaultValue: 0
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
        tableName: 'tbl_seven_up',
        timestamps: true,
        createdAt: 'added_date',
        updatedAt: 'updated_date'
    })

    SevenUp.associate = (models) => {
        SevenUp.hasMany(models.SevenUpBet, { foreignKey: 'seven_up_id', as: 'bets' });
    };

    return SevenUp;
}