module.exports = (sequelize, DataTypes) => {
    const SlotGame = sequelize.define("tbl_slot_game", {
        id: {
            type: DataTypes.INTEGER,
            primaryKey: true,
            autoIncrement: true,
            allowNull: false
        },
        room_id: {
            type: DataTypes.INTEGER,
            defaultValue: 0
        },
        main_card: {
            type: DataTypes.STRING,
            defaultValue: ''
        },
        winning: {
            type: DataTypes.TINYINT,
            defaultValue: 0
        },
        status: {
            type: DataTypes.TINYINT,
            defaultValue: 0
        },
        winning_amount: {
            type: DataTypes.FLOAT,
            defaultValue: 0.0
        },
        user_amount: {
            type: DataTypes.FLOAT,
            defaultValue: 0.0
        },
        comission_amount: {
            type: DataTypes.FLOAT,
            defaultValue: 0.0
        },
        random_amount: {
            type: DataTypes.INTEGER,
            defaultValue: 0
        },
        total_amount: {
            type: DataTypes.FLOAT,
            defaultValue: 0.0
        },
        admin_profit: {
            type: DataTypes.FLOAT,
            defaultValue: 0.0
        },
        end_datetime: {
            type: DataTypes.DATE,
            defaultValue: null // Or specify a default date if needed
        },
        random: {
            type: DataTypes.TINYINT,
            defaultValue: 0
        },
        reel_grid: {
            type: DataTypes.JSON,
            allowNull: false,
            defaultValue: {} // Default to an empty object
        },
        winnings: {
            type: DataTypes.JSON,
            allowNull: false,
            defaultValue: [] // Default to an empty array
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
        tableName: 'tbl_slot_game',
        timestamps: true,
        createdAt: 'added_date',
        updatedAt: 'updated_date'
    });

    SlotGame.associate = (models) => {
        SlotGame.hasMany(models.SlotGameBet, { foreignKey: 'slot_game_id' });
    };

    return SlotGame;
};
