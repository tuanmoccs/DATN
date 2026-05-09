import React, {useCallback, useState} from 'react';
import {
  ActivityIndicator,
  Alert,
  RefreshControl,
  ScrollView,
  StatusBar,
  StyleSheet,
  Text,
  TextInput,
  TouchableOpacity,
  View,
} from 'react-native';
import {launchImageLibrary} from 'react-native-image-picker';
import {useFocusEffect} from '@react-navigation/native';
import {useAuth} from '../../contexts/AuthContext';
import profileService from '../../services/profileService';

const ProfileScreen: React.FC = () => {
  const {user, logout, updateUser} = useAuth();
  const [name, setName] = useState(user?.name || '');
  const [email, setEmail] = useState(user?.email || '');
  const [currentPassword, setCurrentPassword] = useState('');
  const [newPassword, setNewPassword] = useState('');
  const [confirmPassword, setConfirmPassword] = useState('');
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [savingProfile, setSavingProfile] = useState(false);
  const [savingPassword, setSavingPassword] = useState(false);
  const [uploadingAvatar, setUploadingAvatar] = useState(false);

  const syncProfile = async (isRefresh = false) => {
    if (isRefresh) {
      setRefreshing(true);
    } else {
      setLoading(true);
    }

    try {
      const response = await profileService.getProfile();
      setName(response.user.name);
      setEmail(response.user.email);
      await updateUser(response.user);
    } catch (error) {
      console.error('Error loading profile', error);
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  };

  useFocusEffect(
    useCallback(() => {
      syncProfile();
    }, []),
  );

  const handleUpdateProfile = async () => {
    setSavingProfile(true);
    try {
      const response = await profileService.updateProfile({name, email});
      if (response.user) {
        await updateUser(response.user);
      }
      Alert.alert('Thành công', response.message || 'Đã cập nhật thông tin.');
    } catch (error: any) {
      Alert.alert('Lỗi', error.response?.data?.message || 'Không thể cập nhật thông tin.');
    } finally {
      setSavingProfile(false);
    }
  };

  const handleChangePassword = async () => {
    setSavingPassword(true);
    try {
      const response = await profileService.changePassword({
        current_password: currentPassword,
        password: newPassword,
        password_confirmation: confirmPassword,
      });
      setCurrentPassword('');
      setNewPassword('');
      setConfirmPassword('');
      Alert.alert('Thành công', response.message);
    } catch (error: any) {
      Alert.alert('Lỗi', error.response?.data?.message || 'Không thể đổi mật khẩu.');
    } finally {
      setSavingPassword(false);
    }
  };

  const handlePickAvatar = async () => {
    const result = await launchImageLibrary({
      mediaType: 'photo',
      selectionLimit: 1,
      quality: 0.8,
    });

    const asset = result.assets?.[0];
    if (!asset?.uri) {
      return;
    }

    setUploadingAvatar(true);
    try {
      const response = await profileService.uploadAvatar({
        uri: asset.uri,
        type: asset.type,
        fileName: asset.fileName,
      });
      if (response.user) {
        await updateUser(response.user);
      }
      Alert.alert('Thành công', response.message);
    } catch (error: any) {
      Alert.alert('Lỗi', error.response?.data?.message || 'Không thể tải avatar.');
    } finally {
      setUploadingAvatar(false);
    }
  };

  if (loading) {
    return (
      <View style={styles.loadingContainer}>
        <ActivityIndicator size="large" color="#0D47A1" />
      </View>
    );
  }

  return (
    <View style={styles.container}>
      <StatusBar barStyle="light-content" backgroundColor="#0D47A1" />
      <View style={styles.header}>
        <Text style={styles.headerTitle}>Cá nhân</Text>
        <Text style={styles.headerSubtitle}>Quản lý thông tin và bảo mật tài khoản</Text>
      </View>

      <ScrollView
        refreshControl={
          <RefreshControl
            refreshing={refreshing}
            onRefresh={() => syncProfile(true)}
            colors={['#0D47A1']}
          />
        }>
        <View style={styles.section}>
          <View style={styles.profileHero}>
            <View style={styles.avatarCircle}>
              <Text style={styles.avatarText}>{(user?.name || name || '?').charAt(0).toUpperCase()}</Text>
            </View>
            <Text style={styles.profileName}>{name}</Text>
            <Text style={styles.profileEmail}>{email}</Text>
            <TouchableOpacity
              style={styles.secondaryButton}
              onPress={handlePickAvatar}
              disabled={uploadingAvatar}>
              {uploadingAvatar ? (
                <ActivityIndicator color="#0D47A1" />
              ) : (
                <Text style={styles.secondaryButtonText}>Đổi avatar</Text>
              )}
            </TouchableOpacity>
          </View>
        </View>

        <View style={styles.section}>
          <Text style={styles.sectionTitle}>Thông tin cá nhân</Text>
          <View style={styles.card}>
            <Text style={styles.label}>Họ tên</Text>
            <TextInput style={styles.input} value={name} onChangeText={setName} />

            <Text style={styles.label}>Email</Text>
            <TextInput
              style={styles.input}
              value={email}
              onChangeText={setEmail}
              autoCapitalize="none"
              keyboardType="email-address"
            />

            <TouchableOpacity
              style={styles.primaryButton}
              onPress={handleUpdateProfile}
              disabled={savingProfile}>
              {savingProfile ? (
                <ActivityIndicator color="#FFFFFF" />
              ) : (
                <Text style={styles.primaryButtonText}>Lưu thay đổi</Text>
              )}
            </TouchableOpacity>
          </View>
        </View>

        <View style={styles.section}>
          <Text style={styles.sectionTitle}>Đổi mật khẩu</Text>
          <View style={styles.card}>
            <Text style={styles.label}>Mật khẩu hiện tại</Text>
            <TextInput
              style={styles.input}
              value={currentPassword}
              onChangeText={setCurrentPassword}
              secureTextEntry
            />

            <Text style={styles.label}>Mật khẩu mới</Text>
            <TextInput
              style={styles.input}
              value={newPassword}
              onChangeText={setNewPassword}
              secureTextEntry
            />

            <Text style={styles.label}>Xác nhận mật khẩu mới</Text>
            <TextInput
              style={styles.input}
              value={confirmPassword}
              onChangeText={setConfirmPassword}
              secureTextEntry
            />

            <TouchableOpacity
              style={styles.primaryButton}
              onPress={handleChangePassword}
              disabled={savingPassword}>
              {savingPassword ? (
                <ActivityIndicator color="#FFFFFF" />
              ) : (
                <Text style={styles.primaryButtonText}>Cập nhật mật khẩu</Text>
              )}
            </TouchableOpacity>
          </View>
        </View>

        <View style={styles.section}>
          <TouchableOpacity style={styles.logoutButton} onPress={logout}>
            <Text style={styles.logoutButtonText}>Đăng xuất</Text>
          </TouchableOpacity>
        </View>
      </ScrollView>
    </View>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#F1F5F9',
  },
  loadingContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    backgroundColor: '#F1F5F9',
  },
  header: {
    backgroundColor: '#0D47A1',
    paddingHorizontal: 20,
    paddingTop: (StatusBar.currentHeight || 0) + 18,
    paddingBottom: 22,
  },
  headerTitle: {
    color: '#FFFFFF',
    fontSize: 26,
    fontWeight: '700',
  },
  headerSubtitle: {
    marginTop: 6,
    color: '#DBEAFE',
  },
  section: {
    paddingHorizontal: 16,
    marginTop: 18,
  },
  sectionTitle: {
    fontSize: 18,
    fontWeight: '700',
    color: '#0F172A',
    marginBottom: 10,
  },
  profileHero: {
    backgroundColor: '#FFFFFF',
    borderRadius: 14,
    alignItems: 'center',
    padding: 20,
  },
  avatarCircle: {
    width: 74,
    height: 74,
    borderRadius: 37,
    backgroundColor: '#DBEAFE',
    justifyContent: 'center',
    alignItems: 'center',
  },
  avatarText: {
    fontSize: 30,
    fontWeight: '700',
    color: '#1D4ED8',
  },
  profileName: {
    marginTop: 12,
    fontSize: 20,
    fontWeight: '700',
    color: '#0F172A',
  },
  profileEmail: {
    marginTop: 4,
    color: '#64748B',
  },
  card: {
    backgroundColor: '#FFFFFF',
    borderRadius: 14,
    padding: 16,
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
    marginBottom: 14,
    backgroundColor: '#F8FAFC',
    color: '#0F172A',
  },
  primaryButton: {
    backgroundColor: '#0D47A1',
    borderRadius: 8,
    alignItems: 'center',
    paddingVertical: 14,
  },
  primaryButtonText: {
    color: '#FFFFFF',
    fontWeight: '700',
  },
  secondaryButton: {
    marginTop: 14,
    borderWidth: 1,
    borderColor: '#0D47A1',
    borderRadius: 999,
    paddingHorizontal: 18,
    paddingVertical: 10,
  },
  secondaryButtonText: {
    color: '#0D47A1',
    fontWeight: '700',
  },
  logoutButton: {
    marginBottom: 24,
    backgroundColor: '#DC2626',
    borderRadius: 10,
    alignItems: 'center',
    paddingVertical: 14,
  },
  logoutButtonText: {
    color: '#FFFFFF',
    fontWeight: '700',
    fontSize: 15,
  },
});

export default ProfileScreen;
