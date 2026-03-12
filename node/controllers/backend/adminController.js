const { HTTP_NOT_FOUND, HTTP_OK, HTTP_SERVER_ERROR } = require('../../constants');
const db = require('../../models')
const jwt = require('jsonwebtoken');
const AdminModel = db.admin

const register = async (req, res) => {
  // console.log(process.env.JWTKEY);
  if (!req.body.name || !req.body.password || !req.body.email) {
    return res.status(200).json({
      code: HTTP_NOT_FOUND,
      message: 'Please Provide Name Password And Email.',
    });
  }

  try {
    const password = req.body.password;
    const existingAdmin = await AdminModel.findOne({ where: { email_id: req.body.email } });
    if (existingAdmin) {
      return res.status(200).json({
        code: '406',
        message: 'Email Already registered.',
      });
    }

    // Create the admin
    const adminData = await AdminModel.create({
      first_name: req.body.name,
      email_id: req.body.email,
      password: bcrypt.hashSync(password, 10), // Hash the password
      sw_password: password,
    });
    let admin = { ...adminData.toJSON() };
    delete admin.password;
    delete admin.sw_password;
    jwt.sign({ admin }, process.env.JWTKEY, (err, token) => {
      if (err) {
        return res.status(200).json({
          code: '400',
          message: err,
        });
      }
      else {
        return res.status(200).json({
          code: '200',
          message: 'Thanks For Registering With Us.',
          admin,
          token: token,
        });
      }
    })

  } catch (error) {
    console.error('Error During Sign-In:', error);
    return res.status(500).json({
      code: '500',
      message: error,
    });
  }
};

const login = async (req, res) => {
  if (!req.body.email || !req.body.password) {
    return res.status(200).json({
      code: HTTP_NOT_FOUND,
      message: 'Please Provide Email And Password.',
    });
  }
  try {

    const adminData = await AdminModel.findOne({ where: { email_id: req.body.email } });
    if (adminData) {
      const admin = { ...adminData.toJSON() };
      if (req.body.password == admin.password) {
        delete admin.password;
        delete admin.help_support;
        delete admin.terms;
        delete admin.contact_us;
        delete admin.privacy_policy;

        // delete admin.sw_password;
        jwt.sign({ admin }, process.env.JWTKEY, (err, token) => {
          if (err) {
            return res.status(200).json({
              code: '406',
              message: err,
            });
          }
          else {
            return res.status(200).json({
              code: HTTP_OK,
              message: 'You Have Successfully Logged In',
              admin,
              token: token,
            });
          }
        })

      } else {
        return res.status(200).json({
          code: '406',
          message: 'Please Enter Valid Password',
        });
      }
    } else {
      return res.status(200).json({
        code: '406',
        message: 'Please Enter Valid Email',
      });
    }
  } catch (error) {
    console.error('Error During Login:', error);
    return res.status(500).json({
      code: '500',
      message: error,
    });
  }
};

const viewAdmin = async (req, res) => {
  try {
    const adminData = await AdminModel.findAll();
    if (adminData) {
      // const admins = adminData.map((adminInstance) => adminInstance.toJSON());
      const admin = adminData.map((admin) => {
        delete admin.password;
        return admin;
      });
      return res.status(HTTP_OK).json({
        code: HTTP_OK,
        admins: admin,
      });
    }
    else {
      return res.status(HTTP_OK).json({
        code: HTTP_SERVER_ERROR,
        message: 'Something Went Wrong',
      });
    }
  } catch (error) {
    console.error(error);
    return res.status(HTTP_OK).json({
      code: HTTP_SERVER_ERROR,
      message: 'Something Went Wrong',
    });
  }

}
const profile = async (req, res) => {
  if (!req.body.email) {
    return res.status(200).json({
      code: HTTP_NOT_FOUND,
      message: 'Please Provide Email .',
    });
  }
  const adminData = await AdminModel.findOne({ where: { email_id: req.body.email } });
  if (adminData) {
    const admin = { ...adminData.toJSON() };
    // console.log(admin,'admin')
    delete admin.password;
    // delete admin.sw_password;
    return res.status(HTTP_OK).json({
      code: HTTP_OK,
      admin: admin
    })
  }
  else {
    return res.status(HTTP_OK).json({
      code: HTTP_SERVER_ERROR,
      message: 'Something Went Wrong'
    })
  }
}

const updateProfile = async (req, res) => {
  try {
    const { id, email_id, password } = req.body;
    // console.log(req.body)
    if (!email_id || !password) {
      return res.status(HTTP_OK).json({
        code: HTTP_NOT_FOUND,
        message: 'Please provide both email and password.',
      });
    }

    const adminId = req.body.id;
    const updatedAdmin = await AdminModel.update(
      { email_id, password, sw_password: password },
      {
        where: {
          id: adminId,
        },
      }
    );

    if (updatedAdmin) {
      return res.status(200).json({
        code: HTTP_OK,
        message: 'Profile updated successfully',
      });
    } else {
      return res.status(HTTP_OK).json({
        code: HTTP_SERVER_ERROR,
        message: 'Failed to update profile',
      });
    }
  } catch (error) {
    console.error('Error:', error);
    return res.status(HTTP_SERVER_ERROR).json({
      code: HTTP_SERVER_ERROR,
      message: 'Something went wrong',
    });
  }
};




module.exports = {
  login,
  register,
  profile,
  updateProfile,
  viewAdmin,
}