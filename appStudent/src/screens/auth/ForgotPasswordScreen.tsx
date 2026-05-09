import React, {useState} from 'react';
import {
  ActivityIndicator,
  KeyboardAvoidingView,
  Platform,
  ScrollView,
  StyleSheet,
  Text,
  TextInput,
  TouchableOpacity,
  View,
} from 'react-native';
import {NativeStackNavigationProp} from '@react-navigation/native-stack';
import authService from '../../services/authService';
import {AuthStackParamList} from '../../navigation/AuthNavigator';

type ForgotPasswordScreenProps = {
  navigation: NativeStackNavigationProp<AuthStackParamList, 'ForgotPassword'>;
};

const ForgotPasswordScreen: React.FC<ForgotPasswordScreenProps> = ({
  navigation,
}) => {
  const [step, setStep] = useState<1 | 2 | 3>(1);
  const [email, setEmail] = useState('');
  const [otp, setOtp] = useState('');
  const [password, setPassword] = useState('');
  const [passwordConfirmation, setPasswordConfirmation] = useState('');
  const [loading, setLoading] = useState(false);
  const [errors, setErrors] = useState<Record<string, string[]>>({});
  const [message, setMessage] = useState('');
  const [messageType, setMessageType] = useState<'success' | 'error' | ''>('');

  const setBanner = (type: 'success' | 'error', text: string) => {
    setMessage(text);
    setMessageType(type);
  };

  const handleSendOtp = async () => {
    setLoading(true);
    setErrors({});
    setMessage('');

    try {
      const response = await authService.sendForgotPasswordOtp({email});
      setBanner('success', response.message);
      setStep(2);
    } catch (error: any) {
      if (error.response?.status === 422) {
      setErrors(error.response.data.errors || {});
      }
      setBanner('error', error.response?.data?.message || 'Không thể gửi OTP.');
    } finally {
      setLoading(false);
    }
  };

  const handleVerifyOtp = async () => {
    setLoading(true);
    setErrors({});
    setMessage('');

    try {
      const response = await authService.verifyForgotPasswordOtp({email, otp});
      setBanner('success', response.message);
      setStep(3);
    } catch (error: any) {
      if (error.response?.status === 422) {
        setErrors(error.response.data.errors || {});
      }
      setBanner('error', error.response?.data?.message || 'OTP không hợp lệ.');
    } finally {
      setLoading(false);
    }
  };

  const handleResetPassword = async () => {
    setLoading(true);
    setErrors({});
    setMessage('');

    try {
      const response = await authService.resetPassword({
        email,
        otp,
        password,
        password_confirmation: passwordConfirmation,
      });
      setBanner('success', response.message);
      setTimeout(() => {
        navigation.navigate('Login');
      }, 800);
    } catch (error: any) {
      if (error.response?.status === 422) {
        setErrors(error.response.data.errors || {});
      }
      setBanner('error', error.response?.data?.message || 'Đặt lại mật khẩu thất bại.');
    } finally {
      setLoading(false);
    }
  };

  const renderStepContent = () => {
    if (step === 1) {
      return (
        <>
          <Text style={styles.stepTitle}>Gửi OTP</Text>
          <Text style={styles.stepText}>Nhập email đã đăng ký tài khoản học sinh.</Text>
          <View style={styles.inputGroup}>
            <Text style={styles.label}>Email</Text>
            <TextInput
              style={[styles.input, errors.email && styles.inputError]}
              value={email}
              onChangeText={setEmail}
              placeholder="student@email.com"
              placeholderTextColor="#9CA3AF"
              keyboardType="email-address"
              autoCapitalize="none"
            />
            {errors.email ? <Text style={styles.errorText}>{errors.email[0]}</Text> : null}
          </View>
          <TouchableOpacity
            style={styles.actionButton}
            onPress={handleSendOtp}
            disabled={loading}>
            {loading ? <ActivityIndicator color="#FFFFFF" /> : <Text style={styles.actionText}>Gửi OTP</Text>}
          </TouchableOpacity>
        </>
      );
    }

    if (step === 2) {
      return (
        <>
          <Text style={styles.stepTitle}>Xác minh OTP</Text>
          <Text style={styles.stepText}>Nhập mã OTP đã gửi đến {email}.</Text>
          <View style={styles.inputGroup}>
            <Text style={styles.label}>OTP</Text>
            <TextInput
              style={[styles.input, errors.otp && styles.inputError]}
              value={otp}
              onChangeText={setOtp}
              placeholder="6 số"
              placeholderTextColor="#9CA3AF"
              keyboardType="number-pad"
              maxLength={6}
            />
            {errors.otp ? <Text style={styles.errorText}>{errors.otp[0]}</Text> : null}
          </View>
          <TouchableOpacity
            style={styles.actionButton}
            onPress={handleVerifyOtp}
            disabled={loading}>
            {loading ? (
              <ActivityIndicator color="#FFFFFF" />
            ) : (
              <Text style={styles.actionText}>Xác minh</Text>
            )}
          </TouchableOpacity>
        </>
      );
    }

    return (
      <>
        <Text style={styles.stepTitle}>Đặt lại mật khẩu</Text>
        <Text style={styles.stepText}>Nhập mật khẩu mới cho tài khoản.</Text>
        <View style={styles.inputGroup}>
          <Text style={styles.label}>Mật khẩu mới</Text>
          <TextInput
            style={[styles.input, errors.password && styles.inputError]}
            value={password}
            onChangeText={setPassword}
            placeholder="Tối thiểu 8 ký tự"
            placeholderTextColor="#9CA3AF"
            secureTextEntry
          />
          {errors.password ? (
            <Text style={styles.errorText}>{errors.password[0]}</Text>
          ) : null}
        </View>
        <View style={styles.inputGroup}>
          <Text style={styles.label}>Xác nhận mật khẩu</Text>
          <TextInput
            style={styles.input}
            value={passwordConfirmation}
            onChangeText={setPasswordConfirmation}
            placeholder="Nhập lại mật khẩu mới"
            placeholderTextColor="#9CA3AF"
            secureTextEntry
          />
        </View>
        <TouchableOpacity
          style={styles.actionButton}
          onPress={handleResetPassword}
          disabled={loading}>
          {loading ? (
            <ActivityIndicator color="#FFFFFF" />
          ) : (
            <Text style={styles.actionText}>Cập nhật mật khẩu</Text>
          )}
        </TouchableOpacity>
      </>
    );
  };

  return (
    <KeyboardAvoidingView
      style={styles.container}
      behavior={Platform.OS === 'ios' ? 'padding' : 'height'}>
      <ScrollView contentContainerStyle={styles.scrollContent} keyboardShouldPersistTaps="handled">
        <TouchableOpacity onPress={() => navigation.goBack()}>
          <Text style={styles.backText}>Quay lại đăng nhập</Text>
        </TouchableOpacity>

        <View style={styles.card}>
          <Text style={styles.title}>Quên mật khẩu</Text>
          {/* <Text style={styles.subtitle}>Không cần liên hệ admin để lấy lại tài khoản.</Text> */}

          {/*<View style={styles.stepBadge}>
            <Text style={styles.stepBadgeText}>Bước {step}/3</Text>
          </View>*/}

          {message ? (
            <View
              style={[
                styles.banner,
                messageType === 'success' ? styles.bannerSuccess : styles.bannerError,
              ]}>
              <Text
                style={[
                  styles.bannerText,
                  messageType === 'success'
                    ? styles.bannerTextSuccess
                    : styles.bannerTextError,
                ]}>
                {message}
              </Text>
            </View>
          ) : null}

          {renderStepContent()}
        </View>
      </ScrollView>
    </KeyboardAvoidingView>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#0D47A1',
  },
  scrollContent: {
    flexGrow: 1,
    justifyContent: 'center',
    padding: 24,
  },
  backText: {
    color: '#DBEAFE',
    fontWeight: '600',
    marginBottom: 16,
  },
  card: {
    backgroundColor: '#FFFFFF',
    borderRadius: 12,
    padding: 24,
  },
  title: {
    fontSize: 28,
    fontWeight: '700',
    color: '#0F172A',
  },
  subtitle: {
    marginTop: 8,
    marginBottom: 16,
    color: '#64748B',
    lineHeight: 20,
  },
  stepBadge: {
    alignSelf: 'flex-start',
    backgroundColor: '#DBEAFE',
    borderRadius: 999,
    paddingHorizontal: 12,
    paddingVertical: 6,
    marginBottom: 16,
  },
  stepBadgeText: {
    color: '#1D4ED8',
    fontWeight: '700',
  },
  banner: {
    borderRadius: 8,
    padding: 12,
    marginBottom: 16,
  },
  bannerSuccess: {
    backgroundColor: '#ECFDF5',
  },
  bannerError: {
    backgroundColor: '#FEF2F2',
  },
  bannerText: {
    textAlign: 'center',
  },
  bannerTextSuccess: {
    color: '#065F46',
  },
  bannerTextError: {
    color: '#B91C1C',
  },
  stepTitle: {
    fontSize: 18,
    fontWeight: '700',
    color: '#0F172A',
    marginBottom: 6,
  },
  stepText: {
    color: '#64748B',
    marginBottom: 16,
    lineHeight: 20,
  },
  inputGroup: {
    marginBottom: 14,
  },
  label: {
    marginBottom: 6,
    color: '#334155',
    fontWeight: '600',
  },
  input: {
    borderWidth: 1,
    borderColor: '#CBD5E1',
    borderRadius: 8,
    paddingHorizontal: 12,
    paddingVertical: 13,
    color: '#0F172A',
    backgroundColor: '#F8FAFC',
  },
  inputError: {
    borderColor: '#DC2626',
  },
  errorText: {
    marginTop: 4,
    color: '#DC2626',
    fontSize: 12,
  },
  actionButton: {
    marginTop: 8,
    backgroundColor: '#0D47A1',
    borderRadius: 8,
    paddingVertical: 14,
    alignItems: 'center',
  },
  actionText: {
    color: '#FFFFFF',
    fontWeight: '700',
    fontSize: 15,
  },
});

export default ForgotPasswordScreen;
