import React, {useState, useCallback} from 'react';
import {
  View,
  Text,
  StyleSheet,
  FlatList,
  TouchableOpacity,
  RefreshControl,
  Modal,
  TextInput,
  ActivityIndicator,
  StatusBar,
} from 'react-native';
import {useFocusEffect, useNavigation} from '@react-navigation/native';
import {NativeStackNavigationProp} from '@react-navigation/native-stack';
import classService, {ClassInfo} from '../../services/classService';
import {MainStackParamList} from '../../navigation/MainNavigator';

type NavigationProp = NativeStackNavigationProp<MainStackParamList, 'MainTabs'>;

const ClassesScreen: React.FC = () => {
  const navigation = useNavigation<NavigationProp>();
  const [classes, setClasses] = useState<ClassInfo[]>([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);

  // Join class modal
  const [showJoinModal, setShowJoinModal] = useState(false);
  const [joinCode, setJoinCode] = useState('');
  const [joining, setJoining] = useState(false);
  const [joinResult, setJoinResult] = useState<{
    type: 'success' | 'error';
    message: string;
  } | null>(null);

  const fetchClasses = async (isRefresh = false) => {
    if (isRefresh) {
      setRefreshing(true);
    } else {
      setLoading(true);
    }
    try {
      const response = await classService.getMyClasses();
      if (response.success) {
        setClasses(response.data || []);
      }
    } catch (error: any) {
      console.error('Error fetching classes:', error);
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  };

  useFocusEffect(
    useCallback(() => {
      fetchClasses();
    }, []),
  );

  const handleJoinClass = async () => {
    const trimmed = joinCode.trim();
    if (trimmed.length !== 6) {
      setJoinResult({type: 'error', message: 'Mã lớp học phải có 6 ký tự'});
      return;
    }

    setJoining(true);
    setJoinResult(null);

    try {
      const response = await classService.joinClass(trimmed);
      if (response.success) {
        setJoinResult({type: 'success', message: response.message});
        setJoinCode('');
        fetchClasses();
      } else {
        setJoinResult({type: 'error', message: response.message});
      }
    } catch (error: any) {
      const msg =
        error.response?.data?.message ||
        'Có lỗi xảy ra. Vui lòng thử lại sau.';
      setJoinResult({type: 'error', message: msg});
    } finally {
      setJoining(false);
    }
  };

  const closeJoinModal = () => {
    setShowJoinModal(false);
    setJoinCode('');
    setJoinResult(null);
  };

  const getStatusBadge = (status?: string) => {
    if (status === 'pending') {
      return {label: 'Chờ duyệt', bg: '#FEF3C7', color: '#92400E'};
    }
    return {label: 'Đang học', bg: '#D1FAE5', color: '#065F46'};
  };

  const renderClassItem = ({item}: {item: ClassInfo}) => {
    const badge = getStatusBadge(item.enrollment_status);
    const isPending = item.enrollment_status === 'pending';

    return (
      <TouchableOpacity
        style={styles.classCard}
        activeOpacity={isPending ? 1 : 0.7}
        onPress={() => {
          if (!isPending) {
            navigation.navigate('ClassDetail', {classId: item.id});
          }
        }}>
        {/* Header */}
        <View style={styles.cardHeader}>
          <View style={styles.cardHeaderLeft}>
            <View style={[styles.classIcon, isPending && styles.classIconPending]}>
              <Text style={styles.classIconText}>
                {item.name?.charAt(0)?.toUpperCase() || '?'}
              </Text>
            </View>
            <View style={styles.cardTitleWrap}>
              <Text style={styles.className} numberOfLines={1}>
                {item.name}
              </Text>
              <Text style={styles.classSemester}>
                {item.semester || 'Chưa xác định'}
              </Text>
            </View>
          </View>
          <View style={[styles.statusBadge, {backgroundColor: badge.bg}]}>
            <Text style={[styles.statusText, {color: badge.color}]}>
              {badge.label}
            </Text>
          </View>
        </View>

        {/* Teacher */}
        <View style={styles.teacherRow}>
          <Text style={styles.teacherLabel}>👨‍🏫</Text>
          <Text style={styles.teacherName}>
            {item.teacher?.name || 'Chưa rõ'}
          </Text>
        </View>

        {/* Stats */}
        {!isPending && (
          <View style={styles.statsRow}>
            <View style={styles.statItem}>
              <Text style={styles.statIcon}>👥</Text>
              <Text style={styles.statValue}>{item.student_count || 0} học sinh</Text>
            </View>
            <View style={styles.statItem}>
              <Text style={styles.statIcon}>📖</Text>
              <Text style={styles.statValue}>{item.lesson_count || 0} bài học</Text>
            </View>
          </View>
        )}

        {isPending && (
          <View style={styles.pendingNote}>
            <Text style={styles.pendingNoteText}>
              ⏳ Đang chờ giáo viên duyệt yêu cầu
            </Text>
          </View>
        )}
      </TouchableOpacity>
    );
  };

  const renderEmpty = () => (
    <View style={styles.emptyContainer}>
      <Text style={styles.emptyIcon}>📚</Text>
      <Text style={styles.emptyTitle}>Chưa có lớp học nào</Text>
      <Text style={styles.emptySubtitle}>
        Nhấn nút "Tham gia lớp" để nhập mã lớp học
      </Text>
      <TouchableOpacity
        style={styles.emptyButton}
        onPress={() => setShowJoinModal(true)}
        activeOpacity={0.8}>
        <Text style={styles.emptyButtonText}>+ Tham gia lớp</Text>
      </TouchableOpacity>
    </View>
  );

  return (
    <View style={styles.container}>
      <StatusBar barStyle="dark-content" backgroundColor="#FFFFFF" />

      {/* Header */}
      <View style={styles.header}>
        <Text style={styles.headerTitle}>Lớp học của tôi</Text>
        <TouchableOpacity
          style={styles.joinButton}
          onPress={() => setShowJoinModal(true)}
          activeOpacity={0.8}>
          <Text style={styles.joinButtonText}>+ Tham gia</Text>
        </TouchableOpacity>
      </View>

      {/* Content */}
      {loading ? (
        <View style={styles.loadingContainer}>
          <ActivityIndicator size="large" color="#2563EB" />
        </View>
      ) : (
        <FlatList
          data={classes}
          renderItem={renderClassItem}
          keyExtractor={item => String(item.id)}
          contentContainerStyle={[
            styles.listContent,
            classes.length === 0 && styles.emptyListContent,
          ]}
          ListEmptyComponent={renderEmpty}
          refreshControl={
            <RefreshControl
              refreshing={refreshing}
              onRefresh={() => fetchClasses(true)}
              colors={['#2563EB']}
            />
          }
          showsVerticalScrollIndicator={false}
        />
      )}

      {/* Join Class Modal */}
      <Modal
        visible={showJoinModal}
        animationType="slide"
        transparent
        onRequestClose={closeJoinModal}>
        <View style={styles.modalOverlay}>
          <View style={styles.modalContent}>
            {/* Modal Header */}
            <View style={styles.modalHeader}>
              <Text style={styles.modalTitle}>Tham gia lớp học</Text>
              <TouchableOpacity onPress={closeJoinModal}>
                <Text style={styles.modalClose}>✕</Text>
              </TouchableOpacity>
            </View>

            <Text style={styles.modalSubtitle}>
              Nhập mã lớp học do giáo viên cung cấp
            </Text>

            {/* Code Input */}
            <TextInput
              style={styles.codeInput}
              value={joinCode}
              onChangeText={text => setJoinCode(text.toUpperCase())}
              placeholder="VD: ABC123"
              placeholderTextColor="#9CA3AF"
              maxLength={6}
              autoCapitalize="characters"
              autoCorrect={false}
              editable={!joining}
            />
            <Text style={styles.codeHint}>Mã lớp gồm 6 ký tự chữ và số</Text>

            {/* Result */}
            {joinResult && (
              <View
                style={[
                  styles.resultBox,
                  joinResult.type === 'success'
                    ? styles.resultSuccess
                    : styles.resultError,
                ]}>
                <Text
                  style={[
                    styles.resultText,
                    joinResult.type === 'success'
                      ? styles.resultTextSuccess
                      : styles.resultTextError,
                  ]}>
                  {joinResult.type === 'success' ? '✅ ' : '❌ '}
                  {joinResult.message}
                </Text>
              </View>
            )}

            {/* Buttons */}
            <View style={styles.modalButtons}>
              <TouchableOpacity
                style={styles.cancelButton}
                onPress={closeJoinModal}>
                <Text style={styles.cancelButtonText}>Hủy</Text>
              </TouchableOpacity>
              <TouchableOpacity
                style={[
                  styles.submitButton,
                  (joining || joinCode.trim().length === 0) &&
                    styles.submitButtonDisabled,
                ]}
                onPress={handleJoinClass}
                disabled={joining || joinCode.trim().length === 0}
                activeOpacity={0.8}>
                {joining ? (
                  <ActivityIndicator color="#FFFFFF" size="small" />
                ) : (
                  <Text style={styles.submitButtonText}>Gửi yêu cầu</Text>
                )}
              </TouchableOpacity>
            </View>
          </View>
        </View>
      </Modal>
    </View>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#F3F4F6',
  },
  header: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingHorizontal: 20,
    paddingVertical: 16,
    backgroundColor: '#FFFFFF',
    borderBottomWidth: 1,
    borderBottomColor: '#E5E7EB',
  },
  headerTitle: {
    fontSize: 20,
    fontWeight: '700',
    color: '#1F2937',
  },
  joinButton: {
    backgroundColor: '#2563EB',
    paddingHorizontal: 16,
    paddingVertical: 8,
    borderRadius: 8,
  },
  joinButtonText: {
    color: '#FFFFFF',
    fontSize: 14,
    fontWeight: '600',
  },
  loadingContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
  },
  listContent: {
    padding: 16,
    gap: 12,
  },
  emptyListContent: {
    flex: 1,
  },
  // Class Card
  classCard: {
    backgroundColor: '#FFFFFF',
    borderRadius: 16,
    padding: 16,
    shadowColor: '#000',
    shadowOffset: {width: 0, height: 1},
    shadowOpacity: 0.05,
    shadowRadius: 4,
    elevation: 2,
    marginBottom: 4,
  },
  cardHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'flex-start',
    marginBottom: 12,
  },
  cardHeaderLeft: {
    flexDirection: 'row',
    alignItems: 'center',
    flex: 1,
    marginRight: 12,
  },
  classIcon: {
    width: 44,
    height: 44,
    borderRadius: 12,
    backgroundColor: '#DBEAFE',
    justifyContent: 'center',
    alignItems: 'center',
    marginRight: 12,
  },
  classIconPending: {
    backgroundColor: '#FEF3C7',
  },
  classIconText: {
    fontSize: 18,
    fontWeight: '700',
    color: '#2563EB',
  },
  cardTitleWrap: {
    flex: 1,
  },
  className: {
    fontSize: 16,
    fontWeight: '600',
    color: '#1F2937',
    marginBottom: 2,
  },
  classSemester: {
    fontSize: 13,
    color: '#6B7280',
  },
  statusBadge: {
    paddingHorizontal: 10,
    paddingVertical: 4,
    borderRadius: 12,
  },
  statusText: {
    fontSize: 12,
    fontWeight: '600',
  },
  teacherRow: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 12,
    paddingLeft: 2,
  },
  teacherLabel: {
    fontSize: 14,
    marginRight: 6,
  },
  teacherName: {
    fontSize: 14,
    color: '#4B5563',
  },
  statsRow: {
    flexDirection: 'row',
    gap: 20,
    paddingLeft: 2,
  },
  statItem: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
  },
  statIcon: {
    fontSize: 13,
  },
  statValue: {
    fontSize: 13,
    color: '#6B7280',
  },
  pendingNote: {
    backgroundColor: '#FFF7ED',
    borderRadius: 8,
    padding: 10,
  },
  pendingNoteText: {
    fontSize: 13,
    color: '#92400E',
    textAlign: 'center',
  },
  // Empty
  emptyContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    paddingHorizontal: 40,
  },
  emptyIcon: {
    fontSize: 56,
    marginBottom: 16,
  },
  emptyTitle: {
    fontSize: 20,
    fontWeight: '700',
    color: '#1F2937',
    marginBottom: 8,
  },
  emptySubtitle: {
    fontSize: 14,
    color: '#6B7280',
    textAlign: 'center',
    lineHeight: 20,
    marginBottom: 24,
  },
  emptyButton: {
    backgroundColor: '#2563EB',
    paddingHorizontal: 24,
    paddingVertical: 12,
    borderRadius: 10,
  },
  emptyButtonText: {
    color: '#FFFFFF',
    fontSize: 15,
    fontWeight: '600',
  },
  // Modal
  modalOverlay: {
    flex: 1,
    backgroundColor: 'rgba(0,0,0,0.5)',
    justifyContent: 'flex-end',
  },
  modalContent: {
    backgroundColor: '#FFFFFF',
    borderTopLeftRadius: 24,
    borderTopRightRadius: 24,
    padding: 24,
    paddingBottom: 40,
  },
  modalHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 8,
  },
  modalTitle: {
    fontSize: 20,
    fontWeight: '700',
    color: '#1F2937',
  },
  modalClose: {
    fontSize: 20,
    color: '#9CA3AF',
    padding: 4,
  },
  modalSubtitle: {
    fontSize: 14,
    color: '#6B7280',
    marginBottom: 20,
  },
  codeInput: {
    backgroundColor: '#F9FAFB',
    borderWidth: 2,
    borderColor: '#D1D5DB',
    borderRadius: 14,
    paddingHorizontal: 20,
    paddingVertical: 14,
    fontSize: 22,
    fontWeight: '700',
    textAlign: 'center',
    letterSpacing: 6,
    color: '#1F2937',
  },
  codeHint: {
    fontSize: 12,
    color: '#9CA3AF',
    textAlign: 'center',
    marginTop: 8,
    marginBottom: 16,
  },
  resultBox: {
    padding: 12,
    borderRadius: 10,
    marginBottom: 16,
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
  modalButtons: {
    flexDirection: 'row',
    gap: 12,
  },
  cancelButton: {
    flex: 1,
    paddingVertical: 14,
    borderRadius: 10,
    borderWidth: 1,
    borderColor: '#D1D5DB',
    alignItems: 'center',
  },
  cancelButtonText: {
    fontSize: 15,
    fontWeight: '600',
    color: '#6B7280',
  },
  submitButton: {
    flex: 1,
    backgroundColor: '#2563EB',
    paddingVertical: 14,
    borderRadius: 10,
    alignItems: 'center',
  },
  submitButtonDisabled: {
    opacity: 0.5,
  },
  submitButtonText: {
    color: '#FFFFFF',
    fontSize: 15,
    fontWeight: '600',
  },
});

export default ClassesScreen;
