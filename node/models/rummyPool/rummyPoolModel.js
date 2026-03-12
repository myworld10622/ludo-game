module.exports = (sequelize, DataTypes) => {
    const RummyPool = sequelize.define("tbl_rummy_pool", {
        id: {
            type: DataTypes.INTEGER,
            primaryKey: true,
            autoIncrement: true
        },
        table_id: {
            type: DataTypes.INTEGER,
        },
        joker: {
            type: DataTypes.STRING,
        },
        amount: {
            type: DataTypes.BIGINT,
        },
        winner_id: {
            type: DataTypes.INTEGER,
        },
        user_winning_amt: {
            type: DataTypes.FLOAT,
        },
        admin_winning_amt: {
            type: DataTypes.FLOAT
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
        },
        isDeleted: {
            type: DataTypes.TINYINT,
            allowNull: false,
            defaultValue: 0
        }
    }, {
        tableName: 'tbl_rummy_pool',
        timestamps: true,
        createdAt: 'added_date',
        updatedAt: 'updated_date',
        defaultScope: {
            where: {
                isDeleted: 0
            }
        },
        scopes: {
            withDeleted: {
                where: {}
            }
        },
        indexes: [
            {
                name: 'idx_table_id',
                fields: ['table_id']
            },
            {
                name: 'idx_isDeleted',
                fields: ['isDeleted']
            }
        ]
    })

    return RummyPool;
}