import React, {useState} from 'react';
import {
  View,
  Text,
  TextInput,
  TouchableOpacity,
  StyleSheet,
  KeyboardAvoidingView,
  Platform,
  Alert,
  ActivityIndicator,
} from 'react-native';
import classService from '../../services/classService';

const JoinClassScreen: React.FC = () => {
  const [code, setCode] = useState('');
  const [loading, setLoading] = useState(false);
  const [result, setResult] = useState<{
    type: 'success' | 'error';
    message: string;
  } | null>(null);

  const handleJoin = async () => {
    const trimmed = code.trim();
    if (trimmed.length !== 6) {
      setResult({type: 'error', message: 'Mã lớp học phải có 6 ký tự'});
      return;
    }

    setLoading(true);
    setResult(null);

    try {
      const response = await classService.joinClass(trimmed);
      if (response.success) {
        setResult({type: 'success', message: response.message});
        setCode('');
      } else {
        setResult({type: 'error', message: response.message});
      }
    } catch (error: any) {
      const msg =
        error.response?.data?.message ||
        'Có lỗi xảy ra. Vui lòng thử lại sau.';
      setResult({type: 'error', message: msg});
    } finally {
      setLoading(false);
    }
  };

  return (
    <KeyboardAvoidingView
      style={styles.container}
      behavior={Platform.OS === 'ios' ? 'padding' : undefined}>
      <View style={styles.content}>
        {/* Header */}
        <View style={styles.header}>
          <View style={styles.iconContainer}>
            <Text style={styles.iconText}>🏫</Text>
          </View>
          <Text style={styles.title}>Tham gia lớp học</Text>
          <Text style={styles.subtitle}>
            Nhập mã lớp học do giáo viên cung cấp để gửi yêu cầu tham gia
          </Text>
        </View>

        {/* Input */}
        <View style={styles.inputSection}>
          <Text style={styles.label}>Mã lớp học</Text>
          <TextInput
            style={styles.codeInput}
            value={code}
            onChangeText={text => setCode(text.toUpperCase())}
            placeholder="VD: ABC123"
            placeholderTextColor="#9CA3AF"
            maxLength={6}
            autoCapitalize="characters"
            autoCorrect={false}
            editable={!loading}
          />
          <Text style={styles.hint}>
            Mã lớp gồm 6 ký tự chữ và số
          </Text>
        </View>

        {/* Result Message */}
        {result && (
          <View
            style={[
              styles.resultBox,
              result.type === 'success'
                ? styles.resultSuccess
                : styles.resultError,
            ]}>
            <Text
              style={[
                styles.resultText,
                result.type === 'success'
                  ? styles.resultTextSuccess
                  : styles.resultTextError,
              ]}>
              {result.type === 'success' ? '✅ ' : '❌ '}
              {result.message}
            </Text>
          </View>
        )}

        {/* Submit Button */}
        <TouchableOpacity
          style={[styles.submitButton, loading && styles.submitButtonDisabled]}
          onPress={handleJoin}
          disabled={loading || code.trim().length === 0}
          activeOpacity={0.8}>
          {loading ? (
            <ActivityIndicator color="#FFFFFF" size="small" />
          ) : (
            <Text style={styles.submitButtonText}>Gửi yêu cầu tham gia</Text>
          )}
        </TouchableOpacity>

        {/* Info */}
        <View style={styles.infoBox}>
          <Text style={styles.infoTitle}>📋 Lưu ý:</Text>
          <Text style={styles.infoText}>
            • Sau khi gửi yêu cầu, giáo viên sẽ xem xét và duyệt{'\n'}
            • Bạn sẽ được thông báo khi được chấp nhận vào lớp{'\n'}
            • Mỗi mã lớp chỉ có thể gửi yêu cầu một lần
          </Text>
        </View>
      </View>
    </KeyboardAvoidingView>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#EEF2FF',
  },
  content: {
    flex: 1,
    padding: 24,
    justifyContent: 'center',
  },
  header: {
    alignItems: 'center',
    marginBottom: 32,
  },
  iconContainer: {
    width: 72,
    height: 72,
    borderRadius: 36,
    backgroundColor: '#DBEAFE',
    justifyContent: 'center',
    alignItems: 'center',
    marginBottom: 16,
  },
  iconText: {
    fontSize: 32,
  },
  title: {
    fontSize: 24,
    fontWeight: '700',
    color: '#1F2937',
    marginBottom: 8,
  },
  subtitle: {
    fontSize: 14,
    color: '#6B7280',
    textAlign: 'center',
    lineHeight: 20,
    paddingHorizontal: 16,
  },
  inputSection: {
    marginBottom: 20,
  },
  label: {
    fontSize: 14,
    fontWeight: '600',
    color: '#374151',
    marginBottom: 8,
  },
  codeInput: {
    backgroundColor: '#FFFFFF',
    borderWidth: 2,
    borderColor: '#D1D5DB',
    borderRadius: 16,
    paddingHorizontal: 20,
    paddingVertical: 16,
    fontSize: 24,
    fontWeight: '700',
    textAlign: 'center',
    letterSpacing: 8,
    color: '#1F2937',
  },
  hint: {
    fontSize: 12,
    color: '#9CA3AF',
    textAlign: 'center',
    marginTop: 8,
  },
  resultBox: {
    padding: 14,
    borderRadius: 12,
    marginBottom: 20,
  },
  resultSuccess: {
    backgroundColor: '#ECFDF5',
    borderWidth: 1,
    borderColor: '#A7F3D0',
  },
  resultError: {
    backgroundColor: '#FEF2F2',
    borderWidth: 1,
    borderColor: '#FECACA',
  },
  resultText: {
    fontSize: 14,
    fontWeight: '500',
    textAlign: 'center',
    lineHeight: 20,
  },
  resultTextSuccess: {
    color: '#065F46',
  },
  resultTextError: {
    color: '#991B1B',
  },
  submitButton: {
    backgroundColor: '#2563EB',
    borderRadius: 12,
    paddingVertical: 16,
    alignItems: 'center',
    marginBottom: 24,
    shadowColor: '#2563EB',
    shadowOffset: {width: 0, height: 4},
    shadowOpacity: 0.2,
    shadowRadius: 8,
    elevation: 4,
  },
  submitButtonDisabled: {
    opacity: 0.6,
  },
  submitButtonText: {
    color: '#FFFFFF',
    fontSize: 16,
    fontWeight: '600',
  },
  infoBox: {
    backgroundColor: '#FFFFFF',
    borderRadius: 12,
    padding: 16,
    borderWidth: 1,
    borderColor: '#E5E7EB',
  },
  infoTitle: {
    fontSize: 14,
    fontWeight: '600',
    color: '#374151',
    marginBottom: 8,
  },
  infoText: {
    fontSize: 13,
    color: '#6B7280',
    lineHeight: 22,
  },
});

export default JoinClassScreen;
