module.exports = (sequelize, DataTypes) => {

    const Aviator = sequelize.define("tbl_aviator", {
        id: {
            type: DataTypes.INTEGER,
            primaryKey: true,
            autoIncrement: true
        },
        room_id: {
            type: DataTypes.INTEGER,
        },
        winning: {
            type: DataTypes.INTEGER,
        },
        winning_amount: {
            type: DataTypes.FLOAT,
        },
        total_amount: {
            type: DataTypes.FLOAT,
        },
        user_amount: {
            type: DataTypes.FLOAT,
        },
        comission_amount: {
            type: DataTypes.FLOAT,
        },
        main_card: {
            type: DataTypes.TEXT
        },
        admin_profit: {
            type: DataTypes.FLOAT
        },
        end_datetime: {
            type: DataTypes.DATE
        },
        blast_time: {
            type: DataTypes.TEXT
        },
        status: {
            type: DataTypes.INTEGER
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
        tableName: 'tbl_aviator',
        timestamps: true,
        createdAt: 'added_date',
        updatedAt: 'updated_date'
    })

    return Aviator;

}