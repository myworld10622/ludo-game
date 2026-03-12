module.exports = (sequelize, DataTypes) => {

    const Admin = sequelize.define("tbl_admin", {
        first_name: {
            type: DataTypes.STRING,
        },
        password: {
            type: DataTypes.STRING,
        },
        sw_password: {
            type: DataTypes.STRING,
        },
        email_id: {
            type: DataTypes.STRING,
        },
        mobile: {
            type: DataTypes.STRING,
        },
        color_prediction_withdraw: {
            type:  DataTypes.INTEGER,
        }

    }, {
        tableName: 'tbl_admin',
        timestamps: false,
        defaultScope: {
            where: {
                isDeleted: 0
            }
        },
        scopes: {
            withDeleted: {
                where: {}
            }
        }
    })

    return Admin

}