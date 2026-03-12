module.exports = (sequelize, DataTypes) => {
    const RummyPoint = sequelize.define("tbl_rummy", {
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
        winner_type: {
            type: DataTypes.TINYINT,
            defaultValue: 0,
            comment: "0=User,1=Bot"
        },
        winning_amount: {
            type: DataTypes.FLOAT,
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
        tableName: 'tbl_rummy',
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

    return RummyPoint;
}