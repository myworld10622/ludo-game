module.exports = (sequelize, DataTypes) => {
    const DragonTiger = sequelize.define("tbl_dragon_tiger", {
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
        random_amount: {
            type: DataTypes.INTEGER,
            defaultValue: 0
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
        tableName: 'tbl_dragon_tiger',
        timestamps: true,
        createdAt: 'added_date',
        updatedAt: 'updated_date'
    })

    // DragonTiger.hasMany(DragonTigerBet, { foreignKey: 'dragon_tiger_id' });
    // DragonTigerBet.associate = (models) => {
    //     DragonTigerBet.belongsTo(models.DragonTiger, { foreignKey: 'dragon_tiger_id' });
    // };

    DragonTiger.associate = (models) => {
        // DragonTiger.hasOne(models.Profile, { as: 'Profile' });
        DragonTiger.hasMany(models.DragonTigerBet, { foreignKey: 'dragon_tiger_id' });
    };

    return DragonTiger;
}